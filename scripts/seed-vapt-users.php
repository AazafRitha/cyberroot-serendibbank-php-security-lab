<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\Identity;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$customerCount = filter_var($argv[1] ?? '10', FILTER_VALIDATE_INT);
$staffCount = filter_var($argv[2] ?? '5', FILTER_VALIDATE_INT);
if ($argc > 3 || $customerCount === false || $staffCount === false ||
    $customerCount < 0 || $customerCount > 100 || $staffCount < 0 || $staffCount > 30) {
    fwrite(STDERR, "Usage: php scripts/seed-vapt-users.php [CUSTOMERS 0-100] [STAFF 0-30]\n");
    exit(1);
}

$pdo = Database::connect();
$locked = false;
$created = [];
$skipped = 0;
try {
    $locked = (int) $pdo->query("SELECT GET_LOCK('serendibbank_vapt_seed', 10)")->fetchColumn() === 1;
    if (!$locked) {
        throw new RuntimeException('Another seed operation is running. Try again later.');
    }
    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
    $customerInsert = $pdo->prepare('INSERT INTO customers (display_name) VALUES (?)');
    $userInsert = $pdo->prepare(
        'INSERT INTO users (customer_id, display_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
    );
    $detailsInsert = $pdo->prepare(
        'INSERT INTO customer_registration_details
         (customer_id, identity_type, identity_fingerprint, identity_last_four,
          address_line1, address_line2, city, postal_code)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $accountInsert = $pdo->prepare(
        "INSERT INTO accounts (customer_id, account_number, account_type, currency, balance)
         VALUES (?, ?, ?, 'LKR', ?)"
    );
    $transactionInsert = $pdo->prepare(
        'INSERT INTO transactions (account_id, posted_at, description, amount)
         VALUES (?, UTC_TIMESTAMP(), ?, ?)'
    );

    foreach (['customer' => $customerCount, 'staff' => $staffCount] as $role => $count) {
        for ($number = 1; $number <= $count; $number++) {
            $label = sprintf('%03d', $number);
            $email = "vapt.$role.$label@example.test";
            $name = 'VAPT ' . ucfirst($role) . ' ' . $label;
            $find->execute([$email]);
            $existing = $find->fetch();
            if ($existing !== false) {
                if ($existing['role'] !== $role) {
                    throw new RuntimeException("Existing seed user has a changed role: $email. Restore it before seeding.");
                }
                $skipped++;
                continue;
            }
            $password = bin2hex(random_bytes(16));
            $customerId = null;
            $accounts = [];
            if ($role === 'customer') {
                $customerInsert->execute([$name]);
                $customerId = (int) $pdo->lastInsertId();
                $document = 'VAPTLAB' . $label;
                $detailsInsert->execute([
                    $customerId, 'passport', Identity::fingerprint('passport', $document),
                    substr($document, -4), "$number Fictional Lab Road",
                    'Synthetic VAPT data', 'Test City', '00000',
                ]);
                foreach (['Everyday account' => '100000.00', 'Savings account' => '250000.00'] as $type => $balance) {
                    $accountNumber = '28' . (string) random_int(1000000000, 9999999999);
                    $accountInsert->execute([$customerId, $accountNumber, $type, $balance]);
                    $accountId = (int) $pdo->lastInsertId();
                    $transactionInsert->execute([$accountId, 'Fictional VAPT opening balance', $balance]);
                    $accounts[] = ['id' => $accountId, 'number' => $accountNumber, 'type' => $type];
                }
            }
            $userInsert->execute([$customerId, $name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $created[] = [
                'email' => $email, 'password' => $password, 'role' => $role,
                'user_id' => (int) $pdo->lastInsertId(), 'customer_id' => $customerId, 'accounts' => $accounts,
            ];
        }
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed failed. Database additions were rolled back. " . $exception->getMessage() . "\n");
    exit(1);
} finally {
    if ($locked) {
        $pdo->query("SELECT RELEASE_LOCK('serendibbank_vapt_seed')");
    }
}

fwrite(STDOUT, json_encode([
    'created_count' => count($created), 'skipped_count' => $skipped,
    'notice' => 'Keep this credentials file private. Passwords are shown only for newly created users.',
    'created' => $created,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
