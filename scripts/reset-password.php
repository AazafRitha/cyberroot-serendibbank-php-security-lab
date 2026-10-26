<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/reset-password.php EMAIL\n");
    exit(1);
}

$password = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$statement = Database::connect()->prepare(
    'UPDATE users SET password_hash = :password_hash, session_version = session_version + 1 WHERE email = :email'
);
$statement->execute([
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'email' => $email,
]);
if ($statement->rowCount() !== 1) {
    fwrite(STDERR, "User was not found.\n");
    exit(1);
}

fwrite(STDOUT, "Password reset. Existing sessions are invalidated. Save this password securely.\n");
fwrite(STDOUT, "Email: $email\nPassword: $password\n");
