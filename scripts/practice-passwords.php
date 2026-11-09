<?php

declare(strict_types=1);

// Read operator-selected practice passwords from private Compose environment.
$variables = [
    'customer' => 'PRACTICE_PASSWORD_CUSTOMER',
    'staff' => 'PRACTICE_PASSWORD_STAFF',
    'admin' => 'PRACTICE_PASSWORD_ADMIN',
    'super_admin' => 'PRACTICE_PASSWORD_SUPER_ADMIN',
];
$passwords = [];
foreach ($variables as $role => $variable) {
    $value = getenv($variable);
    if (!is_string($value) || $value === '') {
        throw new RuntimeException("Set {$variable} in the private .env file before synchronizing practice passwords.");
    }
    $passwords[$role] = $value;
}
return $passwords;
