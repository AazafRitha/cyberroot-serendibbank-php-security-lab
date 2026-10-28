<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class Settings
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|false */
    public function user(int $id): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT id, customer_id, display_name, email, password_hash, role, session_version, last_login_at FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([$id]);
        return $statement->fetch();
    }

    /** @return array<string, mixed> */
    public function preferences(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT account_alerts, product_updates, preferred_contact FROM customer_preferences WHERE user_id = ? LIMIT 1'
        );
        $statement->execute([$userId]);
        return $statement->fetch() ?: ['account_alerts' => 1, 'product_updates' => 0, 'preferred_contact' => 'email'];
    }

    /** @return array<string, mixed>|false */
    public function registrationDetails(int $customerId): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT identity_type, identity_last_four, address_line1, address_line2, city, postal_code,
                    (SELECT account_number FROM accounts WHERE customer_id = d.customer_id ORDER BY id LIMIT 1) AS account_number
             FROM customer_registration_details AS d WHERE customer_id = ? LIMIT 1'
        );
        $statement->execute([$customerId]);
        return $statement->fetch();
    }

    public function saveProfile(int $userId, string $name, string $email, string $role): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET display_name = ?, email = ?, role = ? WHERE id = ?'
        );
        $statement->execute([$name, $email, $role, $userId]);
    }

    public function savePreferences(int $userId, bool $alerts, bool $updates, string $contact): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO customer_preferences (user_id, account_alerts, product_updates, preferred_contact)
             VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE
             account_alerts = VALUES(account_alerts), product_updates = VALUES(product_updates),
             preferred_contact = VALUES(preferred_contact)'
        );
        $statement->execute([$userId, (int) $alerts, (int) $updates, $contact]);
    }

    public function changePassword(int $targetUserId, string $passwordHash): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?'
        );
        $statement->execute([$passwordHash, $targetUserId]);
        return $statement->rowCount() === 1;
    }
}
