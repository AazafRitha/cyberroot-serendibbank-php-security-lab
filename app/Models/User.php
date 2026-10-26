<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class User
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|false */
    public function findByEmail(string $email): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT id, customer_id, display_name, email, password_hash, role, session_version FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        return $statement->fetch();
    }

    public function cooldownSeconds(string $ip): int
    {
        $statement = $this->pdo->prepare(
            'SELECT attempted_at FROM login_attempts
             WHERE attempt_key = :attempt_key AND attempted_at >= UTC_TIMESTAMP() - INTERVAL 15 MINUTE
             ORDER BY attempted_at DESC LIMIT 5'
        );
        $statement->execute(['attempt_key' => hash('sha256', 'ip:' . $ip)]);
        $attempts = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (count($attempts) < 5) {
            return 0;
        }
        $oldest = strtotime((string) end($attempts) . ' UTC');
        return $oldest === false ? 900 : max(1, $oldest + 900 - time());
    }

    public function recordFailedLogin(string $ip): void
    {
        $statement = $this->pdo->prepare('INSERT INTO login_attempts (attempt_key) VALUES (:attempt_key)');
        $statement->execute(['attempt_key' => hash('sha256', 'ip:' . $ip)]);
    }

    public function markSignedIn(int $userId): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }
}
