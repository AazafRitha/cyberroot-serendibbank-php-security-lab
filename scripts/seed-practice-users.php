<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\Identity;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

if (is_file(dirname(__DIR__) . '/private/practice-users.csv')) {
    fwrite(STDERR, "The private user dataset is present. Run scripts/import-practice-users.php instead.\n");
    exit(1);
}

$customers = [
    ['A. Jayasinghe', 'Colombo'],
    ['S. de Silva', 'Kandy'],
    ['R. Wickramasinghe', 'Galle'],
    ['D. Samarasinghe', 'Negombo'],
    ['K. Ranasinghe', 'Matara'],
    ['T. Gunawardena', 'Kurunegala'],
    ['P. Weerasinghe', 'Jaffna'],
    ['F. Nizam', 'Batticaloa'],
    ['M. Rahman', 'Trincomalee'],
    ['L. Fernando', 'Kalutara'],
    ['C. Abeysekara', 'Nuwara Eliya'],
    ['H. Perera', 'Anuradhapura'],
];

$employees = [
    ['staff', 'R. Senanayake', 'practice.staff01@example.test'],
    ['staff', 'N. Wijeratne', 'practice.staff02@example.test'],
    ['staff', 'S. Kumaran', 'practice.staff03@example.test'],
    ['staff', 'A. Fathima', 'practice.staff04@example.test'],
    ['staff', 'D. Bandara', 'practice.staff05@example.test'],
    ['admin', 'M. Dissanayake', 'practice.admin01@example.test'],
    ['admin', 'V. Fernando', 'practice.admin02@example.test'],
    ['super_admin', 'I. Karunaratne', 'practice.superadmin01@example.test'],
];

$pdo = Database::connect();
$findUser = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$insertCustomer = $pdo->prepare('INSERT INTO customers (display_name) VALUES (?)');
$insertUser = $pdo->prepare(
    'INSERT INTO users (customer_id, display_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
);
$insertDetails = $pdo->prepare(
    'INSERT INTO customer_registration_details
     (customer_id, identity_type, identity_fingerprint, identity_last_four, address_line1, address_line2, city, postal_code)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$insertAccount = $pdo->prepare(
    'INSERT INTO accounts (customer_id, account_number, account_type, currency, balance) VALUES (?, ?, ?, ?, ?)'
);
$insertTransaction = $pdo->prepare(
    'INSERT INTO transactions (account_id, posted_at, description, amount) VALUES (?, UTC_TIMESTAMP(), ?, ?)'
);

$passwordFor = static fn (): string => rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$accountNumberFor = static fn (string $prefix): string => $prefix . (string) random_int(1000000000, 9999999999);

fputcsv(STDOUT, ['role', 'email', 'password', 'customer_id', 'everyday_account', 'savings_account'], ',', '"', '');
$created = 0;
$skipped = 0;

foreach ($customers as $offset => [$name, $city]) {
    $number = $offset + 1;
    $email = sprintf('practice.customer%02d@example.test', $number);
    $findUser->execute([$email]);
    if ($findUser->fetchColumn() !== false) {
        $skipped++;
        continue;
    }

    $password = $passwordFor();
    $identity = sprintf('PRACTICE%04d', $number);
    $everydayNumber = $accountNumberFor('28');
    $savingsNumber = $accountNumberFor('29');
    $pdo->beginTransaction();
    try {
        $insertCustomer->execute([$name]);
        $customerId = (int) $pdo->lastInsertId();
        $insertUser->execute([$customerId, $name, $email, password_hash($password, PASSWORD_DEFAULT), 'customer']);
        $insertDetails->execute([
            $customerId,
            'passport',
            Identity::fingerprint('passport', $identity),
            substr($identity, -4),
            $number . ' Lotus Lane',
            null,
            $city,
            sprintf('%05d', 10000 + $number),
        ]);
        $insertAccount->execute([$customerId, $everydayNumber, 'Everyday account', 'LKR', '175000.00']);
        $everydayId = (int) $pdo->lastInsertId();
        $insertAccount->execute([$customerId, $savingsNumber, 'Savings account', 'LKR', '350000.00']);
        $savingsId = (int) $pdo->lastInsertId();
        $insertTransaction->execute([$everydayId, 'Opening deposit', '200000.00']);
        $insertTransaction->execute([$everydayId, 'Utility payment', '-8500.00']);
        $insertTransaction->execute([$everydayId, 'Card purchase', '-16500.00']);
        $insertTransaction->execute([$savingsId, 'Savings deposit', '350000.00']);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    fputcsv(STDOUT, ['customer', $email, $password, $customerId, $everydayNumber, $savingsNumber], ',', '"', '');
    $created++;
}

foreach ($employees as [$role, $name, $email]) {
    $findUser->execute([$email]);
    if ($findUser->fetchColumn() !== false) {
        $skipped++;
        continue;
    }
    $password = $passwordFor();
    $insertUser->execute([null, $name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    fputcsv(STDOUT, [$role, $email, $password, '', '', ''], ',', '"', '');
    $created++;
}

fwrite(STDERR, "Created {$created} users; skipped {$skipped} existing users. Store the CSV privately; passwords cannot be recovered later.\n");
