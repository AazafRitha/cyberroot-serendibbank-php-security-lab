<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\Identity;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$source = dirname(__DIR__) . '/private/practice-users.csv';
$validateOnly = in_array('--validate', $argv, true);
$handle = fopen($source, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Missing private/practice-users.csv. Copy the operator dataset onto this server first.\n");
    exit(1);
}

$expected = [
    'record_id', 'legacy_email', 'role', 'full_name', 'email', 'password',
    'identity_type', 'identity_number', 'address_line1', 'address_line2',
    'city', 'postal_code', 'customer_id_hint', 'everyday_account', 'savings_account',
];
$header = fgetcsv($handle, 0, ',', '"', '');
if ($header !== $expected) {
    throw new RuntimeException('The CSV columns do not match the practice user import format.');
}

$records = [];
$seen = ['record_id' => [], 'legacy_email' => [], 'email' => [], 'password' => [], 'identity_number' => [], 'everyday_account' => [], 'savings_account' => []];
$roles = ['customer' => 0, 'staff' => 0, 'admin' => 0, 'super_admin' => 0];
while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
    if ($values === [null]) {
        continue;
    }
    if (count($values) !== count($expected)) {
        throw new RuntimeException('A CSV row has the wrong number of columns.');
    }
    $row = array_combine($expected, array_map('trim', $values));
    if ($row === false || !array_key_exists($row['role'], $roles)) {
        throw new RuntimeException('A CSV row has an invalid role.');
    }
    $roles[$row['role']]++;
    if (!preg_match('/^[CSAX][0-9]{3}$/D', $row['record_id']) ||
        strlen($row['full_name']) < 5 || strlen($row['full_name']) > 120 ||
        !filter_var($row['email'], FILTER_VALIDATE_EMAIL) ||
        !preg_match('/@(members|team)\.serendibbank\.test$/D', $row['email']) ||
        strlen($row['password']) < 6 || strlen($row['password']) > 72) {
        throw new RuntimeException("Invalid account fields for {$row['record_id']}.");
    }
    if ($row['legacy_email'] !== '' &&
        (!filter_var($row['legacy_email'], FILTER_VALIDATE_EMAIL) || !str_ends_with($row['legacy_email'], '.test'))) {
        throw new RuntimeException("Invalid legacy email for {$row['record_id']}.");
    }
    if ($row['legacy_email'] !== '') {
        $legacyKey = strtolower($row['legacy_email']);
        if (isset($seen['legacy_email'][$legacyKey])) {
            throw new RuntimeException('Duplicate legacy email in the CSV.');
        }
        $seen['legacy_email'][$legacyKey] = true;
    }
    foreach (['record_id', 'email', 'password'] as $field) {
        $key = strtolower($row[$field]);
        if (isset($seen[$field][$key])) {
            throw new RuntimeException("Duplicate {$field} in the CSV.");
        }
        $seen[$field][$key] = true;
    }
    if ($row['role'] === 'customer') {
        $normalized = Identity::normalize($row['identity_type'], $row['identity_number']);
        if ($normalized === null || strlen($row['address_line1']) < 5 ||
            $row['city'] === '' || !preg_match('/^[0-9]{5}$/D', $row['postal_code']) ||
            ($row['customer_id_hint'] !== '' && !preg_match('/^[0-9]+$/D', $row['customer_id_hint'])) ||
            !preg_match('/^28[0-9]{10}$/D', $row['everyday_account']) ||
            !preg_match('/^29[0-9]{10}$/D', $row['savings_account'])) {
            throw new RuntimeException("Invalid customer details for {$row['record_id']}.");
        }
        foreach (['identity_number', 'everyday_account', 'savings_account'] as $field) {
            $key = $row[$field];
            if (isset($seen[$field][$key])) {
                throw new RuntimeException("Duplicate {$field} in the CSV.");
            }
            $seen[$field][$key] = true;
        }
        $row['identity_number'] = $normalized;
    } elseif ($row['identity_type'] !== '' || $row['identity_number'] !== '' ||
        $row['customer_id_hint'] !== '' || $row['everyday_account'] !== '' || $row['savings_account'] !== '') {
        throw new RuntimeException("Non-customer account fields for {$row['record_id']}.");
    }
    $records[] = $row;
}
fclose($handle);

if (count($records) !== 72 || $roles !== ['customer' => 50, 'staff' => 15, 'admin' => 5, 'super_admin' => 2]) {
    throw new RuntimeException('Expected 72 users: 50 customers, 15 staff, 5 admins, 2 super admins.');
}
if ($validateOnly) {
    fwrite(STDOUT, "CSV valid: 72 unique emails and passwords across four roles.\n");
    exit(0);
}

$pdo = Database::connect();
$findUser = $pdo->prepare('SELECT id, customer_id, display_name, email, role, password_hash FROM users WHERE email = ? LIMIT 1');
$findCustomer = $pdo->prepare('SELECT id FROM customers WHERE id = ? LIMIT 1');
$insertCustomer = $pdo->prepare('INSERT INTO customers (display_name) VALUES (?)');
$updateCustomer = $pdo->prepare('UPDATE customers SET display_name = ? WHERE id = ?');
$insertUser = $pdo->prepare('INSERT INTO users (customer_id, display_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)');
$updateUser = $pdo->prepare('UPDATE users SET customer_id = ?, display_name = ?, email = ?, password_hash = ?, role = ?, session_version = session_version + 1 WHERE id = ?');
$findDetails = $pdo->prepare('SELECT customer_id FROM customer_registration_details WHERE customer_id = ?');
$insertDetails = $pdo->prepare(
    'INSERT INTO customer_registration_details
     (customer_id, identity_type, identity_fingerprint, identity_last_four, address_line1, address_line2, city, postal_code)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$updateDetails = $pdo->prepare(
    'UPDATE customer_registration_details SET identity_type = ?, identity_fingerprint = ?, identity_last_four = ?,
     address_line1 = ?, address_line2 = ?, city = ?, postal_code = ? WHERE customer_id = ?'
);
$findAccount = $pdo->prepare('SELECT id, account_number FROM accounts WHERE customer_id = ? AND account_type = ? ORDER BY id LIMIT 1');
$insertAccount = $pdo->prepare('INSERT INTO accounts (customer_id, account_number, account_type, currency, balance) VALUES (?, ?, ?, ?, ?)');
$updateAccount = $pdo->prepare('UPDATE accounts SET account_number = ? WHERE id = ?');
$insertTransaction = $pdo->prepare('INSERT INTO transactions (account_id, posted_at, description, amount) VALUES (?, UTC_TIMESTAMP(), ?, ?)');

$added = 0;
$updated = 0;
$newAccounts = 0;
$pdo->beginTransaction();
try {
    foreach ($records as $row) {
        $findUser->execute([$row['email']]);
        $user = $findUser->fetch();
        if ($row['legacy_email'] !== '') {
            $findUser->execute([$row['legacy_email']]);
            $legacy = $findUser->fetch();
            if ($user !== false && $legacy !== false && (int) $user['id'] !== (int) $legacy['id']) {
                throw new RuntimeException("Two existing accounts match {$row['record_id']}.");
            }
            $user = $user === false ? $legacy : $user;
        }

        $customerId = $user !== false && $user['customer_id'] !== null ? (int) $user['customer_id'] : null;
        if ($row['role'] === 'customer' && $customerId === null && $row['customer_id_hint'] !== '') {
            $findCustomer->execute([(int) $row['customer_id_hint']]);
            if ($findCustomer->fetchColumn() !== false) {
                $customerId = (int) $row['customer_id_hint'];
            }
        }
        if ($row['role'] === 'customer') {
            if ($customerId === null) {
                $insertCustomer->execute([$row['full_name']]);
                $customerId = (int) $pdo->lastInsertId();
            } else {
                $updateCustomer->execute([$row['full_name'], $customerId]);
            }
        }

        if ($user === false) {
            $insertUser->execute([$customerId, $row['full_name'], $row['email'], password_hash($row['password'], PASSWORD_DEFAULT), $row['role']]);
            $added++;
        } else {
            $passwordMatches = password_verify($row['password'], (string) $user['password_hash']);
            if ($user['display_name'] !== $row['full_name'] || $user['email'] !== $row['email'] ||
                $user['role'] !== $row['role'] || $user['customer_id'] != $customerId || !$passwordMatches) {
                $hash = $passwordMatches ? $user['password_hash'] : password_hash($row['password'], PASSWORD_DEFAULT);
                $updateUser->execute([$customerId, $row['full_name'], $row['email'], $hash, $row['role'], (int) $user['id']]);
                $updated++;
            }
        }

        if ($row['role'] !== 'customer') {
            continue;
        }
        $details = [
            $row['identity_type'], Identity::fingerprint($row['identity_type'], $row['identity_number']),
            substr($row['identity_number'], -4), $row['address_line1'],
            $row['address_line2'] === '' ? null : $row['address_line2'], $row['city'], $row['postal_code'],
        ];
        $findDetails->execute([$customerId]);
        if ($findDetails->fetchColumn() === false) {
            $insertDetails->execute(array_merge([$customerId], $details));
        } else {
            $updateDetails->execute(array_merge($details, [$customerId]));
        }

        $customerNumber = (int) substr($row['record_id'], 1);
        foreach ([
            ['Everyday account', $row['everyday_account'], 150000 + $customerNumber * 2500],
            ['Savings account', $row['savings_account'], 300000 + $customerNumber * 5000],
        ] as [$type, $number, $openingBalance]) {
            $findAccount->execute([$customerId, $type]);
            $account = $findAccount->fetch();
            if ($account !== false) {
                if ($account['account_number'] !== $number) {
                    $updateAccount->execute([$number, (int) $account['id']]);
                }
                continue;
            }
            $insertAccount->execute([$customerId, $number, $type, 'LKR', number_format($openingBalance, 2, '.', '')]);
            $accountId = (int) $pdo->lastInsertId();
            $newAccounts++;
            if ($type === 'Everyday account') {
                $insertTransaction->execute([$accountId, 'Opening deposit', number_format($openingBalance + 12000, 2, '.', '')]);
                $insertTransaction->execute([$accountId, 'Utility payment', '-4200.00']);
                $insertTransaction->execute([$accountId, 'Card purchase', '-7800.00']);
            } else {
                $insertTransaction->execute([$accountId, 'Savings deposit', number_format($openingBalance, 2, '.', '')]);
            }
        }
    }
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

fwrite(STDOUT, "Imported 72 users: {$added} added, {$updated} updated, {$newAccounts} accounts opened. Existing balances and transactions were preserved.\n");
