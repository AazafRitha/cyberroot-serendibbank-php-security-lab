<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

$statement = Database::connect()->query(
    "SELECT u.id AS user_id, u.role, u.display_name, u.email, u.is_active AS active, u.customer_id,
            COALESCE(a.account_numbers, '') AS account_numbers,
            COALESCE(d.identity_type, '') AS identity_type,
            COALESCE(d.identity_last_four, '') AS identity_last_four,
            COALESCE(d.address_line1, '') AS address_line1,
            COALESCE(d.address_line2, '') AS address_line2,
            COALESCE(d.city, '') AS city,
            COALESCE(d.postal_code, '') AS postal_code,
            u.created_at, u.last_login_at
     FROM users u
     LEFT JOIN customer_registration_details d ON d.customer_id = u.customer_id
     LEFT JOIN (
         SELECT customer_id,
                GROUP_CONCAT(account_number ORDER BY account_number SEPARATOR '; ') AS account_numbers
         FROM accounts GROUP BY customer_id
     ) a ON a.customer_id = u.customer_id
     ORDER BY FIELD(u.role, 'customer', 'staff', 'admin', 'super_admin'), u.id"
);

$columns = ['user_id', 'role', 'display_name', 'email', 'active', 'customer_id', 'account_numbers', 'identity_type', 'identity_last_four', 'address_line1', 'address_line2', 'city', 'postal_code', 'created_at', 'last_login_at'];
fputcsv(STDOUT, $columns, ',', '"', '');
while ($row = $statement->fetch()) {
    $values = [];
    foreach ($columns as $column) {
        $value = (string) ($row[$column] ?? '');
        // Prevent a name or email from becoming a formula when the CSV is opened in Excel.
        $values[] = preg_match('/^[\s]*[=+@\-]/', $value) ? "'" . $value : $value;
    }
    fputcsv(STDOUT, $values, ',', '"', '');
}
