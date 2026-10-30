<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class UserSession
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, string $sessionId, string $ip, string $agent): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO user_sessions (user_id, session_hash, ip_address, user_agent)
             VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$userId, hash('sha256', $sessionId), substr($ip, 0, 45), substr($agent, 0, 255)]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|false */
    public function find(int $id, int $userId, string $sessionId): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT id, revoked_at FROM user_sessions WHERE id = ? AND user_id = ? AND session_hash = ? LIMIT 1'
        );
        $statement->execute([$id, $userId, hash('sha256', $sessionId)]);
        return $statement->fetch();
    }

    public function touch(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE user_sessions SET last_seen_at = UTC_TIMESTAMP() WHERE id = ?');
        $statement->execute([$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function forUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, ip_address, user_agent, created_at, last_seen_at, revoked_at
             FROM user_sessions WHERE user_id = ? ORDER BY id DESC LIMIT 30'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function revoke(int $id, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE user_sessions SET revoked_at = UTC_TIMESTAMP()
             WHERE id = ? AND user_id = ? AND revoked_at IS NULL'
        );
        $statement->execute([$id, $userId]);
        return $statement->rowCount() === 1;
    }

    public function revokeOthers(int $userId, int $currentId): int
    {
        $statement = $this->pdo->prepare(
            'UPDATE user_sessions SET revoked_at = UTC_TIMESTAMP()
             WHERE user_id = ? AND id <> ? AND revoked_at IS NULL'
        );
        $statement->execute([$userId, $currentId]);
        return $statement->rowCount();
    }
}
