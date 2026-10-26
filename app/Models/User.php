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

    public function isRateLimited(string $email, string $ip): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE attempt_key = :attempt_key AND attempted_at >= UTC_TIMESTAMP() - INTERVAL 15 MINUTE'
        );
        foreach ([['mail:' . $email, 5], ['ip:' . $ip, 25]] as [$value, $limit]) {
            $statement->execute(['attempt_key' => hash('sha256', $value)]);
            if ((int) $statement->fetchColumn() >= $limit) {
                return true;
            }
        }
        return false;
    }

    public function recordFailedLogin(string $email, string $ip): void
    {
        $statement = $this->pdo->prepare('INSERT INTO login_attempts (attempt_key) VALUES (:attempt_key)');
        foreach (['mail:' . $email, 'ip:' . $ip] as $value) {
            $statement->execute(['attempt_key' => hash('sha256', $value)]);
        }
    }

    public function clearEmailAttempts(string $email): void
    {
        $statement = $this->pdo->prepare('DELETE FROM login_attempts WHERE attempt_key = :attempt_key');
        $statement->execute(['attempt_key' => hash('sha256', 'mail:' . $email)]);
    }

    public function markSignedIn(int $userId): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }
}
