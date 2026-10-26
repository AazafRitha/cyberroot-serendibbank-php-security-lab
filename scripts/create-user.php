<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$email = strtolower(trim($argv[1] ?? ''));
$name = trim($argv[2] ?? '');
$role = $argv[3] ?? '';
$customerId = $role === 'customer' ? filter_var($argv[4] ?? null, FILTER_VALIDATE_INT) : null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 ||
    $name === '' || strlen($name) > 120 ||
    !in_array($role, ['customer', 'staff'], true) ||
    ($role === 'customer' && (!$customerId || $customerId < 1))) {
    fwrite(STDERR, "Usage: php scripts/create-user.php EMAIL DISPLAY_NAME customer CUSTOMER_ID\n");
    fwrite(STDERR, "   or: php scripts/create-user.php EMAIL DISPLAY_NAME staff\n");
    exit(1);
}

$pdo = Database::connect();
if ($customerId !== null) {
    $check = $pdo->prepare('SELECT id FROM customers WHERE id = :id');
    $check->execute(['id' => $customerId]);
    if ($check->fetchColumn() === false) {
        fwrite(STDERR, "Customer ID does not exist.\n");
        exit(1);
    }
}

$password = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$statement = $pdo->prepare(
    'INSERT INTO users (customer_id, display_name, email, password_hash, role)
     VALUES (:customer_id, :display_name, :email, :password_hash, :role)'
);
$statement->execute([
    'customer_id' => $customerId,
    'display_name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $role,
]);

fwrite(STDOUT, "User created. Save this password securely; it is shown only once.\n");
fwrite(STDOUT, "Email: $email\nPassword: $password\n");
