<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$passwords = require __DIR__ . '/practice-passwords.php';
$emails = [
    'customer1@example.test',
    'staff@serendibbank.test',
    'admin@serendibbank.test',
    'superadmin@serendibbank.test',
];
foreach (range(1, 12) as $number) {
    $emails[] = sprintf('practice.customer%02d@example.test', $number);
}
foreach (range(1, 5) as $number) {
    $emails[] = sprintf('practice.staff%02d@example.test', $number);
}
foreach (range(1, 2) as $number) {
    $emails[] = sprintf('practice.admin%02d@example.test', $number);
}
$emails[] = 'practice.superadmin01@example.test';

$pdo = Database::connect();
$find = $pdo->prepare('SELECT id, role, password_hash FROM users WHERE email = ? LIMIT 1');
$update = $pdo->prepare(
    'UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?'
);
$updated = 0;
$alreadySet = 0;
$missing = 0;
foreach ($emails as $email) {
    $find->execute([$email]);
    $user = $find->fetch();
    if ($user === false) {
        $missing++;
        continue;
    }
    $password = $passwords[(string) $user['role']] ?? null;
    if ($password === null) {
        throw new RuntimeException("Unexpected role for practice account: {$email}");
    }
    if (password_verify($password, (string) $user['password_hash'])) {
        $alreadySet++;
        continue;
    }
    $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
    $updated++;
}

fwrite(STDOUT, "Practice passwords synchronized: {$updated} changed, {$alreadySet} already set, {$missing} absent.\n");
