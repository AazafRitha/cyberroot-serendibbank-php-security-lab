<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class Management
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, int> */
    public function overview(): array
    {
        $queries = [
            'users' => 'SELECT COUNT(*) FROM users',
            'active_customers' => "SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1",
            'staff' => "SELECT COUNT(*) FROM users WHERE role IN ('staff', 'admin') AND is_active = 1",
            'accounts' => 'SELECT COUNT(*) FROM accounts',
            'pending_applications' => "SELECT COUNT(*) FROM applications WHERE status = 'pending'",
            'open_support' => "SELECT COUNT(*) FROM support_requests WHERE status = 'open'",
        ];
        $summary = [];
        foreach ($queries as $key => $query) {
            $summary[$key] = (int) $this->pdo->query($query)->fetchColumn();
        }
        return $summary;
    }

    /** @return array<int, array<string, mixed>> */
    public function users(string $search = ''): array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.id, u.customer_id, u.display_name, u.email, u.role, u.is_active, u.created_at, u.last_login_at,
                    COUNT(a.id) AS account_count
             FROM users u LEFT JOIN accounts a ON a.customer_id = u.customer_id
             WHERE (:search_empty = 1 OR u.email LIKE :email_search OR u.display_name LIKE :name_search)
             GROUP BY u.id ORDER BY u.id DESC LIMIT 100'
        );
        $statement->execute([
            'search_empty' => $search === '' ? 1 : 0,
            'email_search' => '%' . $search . '%',
            'name_search' => '%' . $search . '%',
        ]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|false */
    public function user(int $id): array|false
    {
        $statement = $this->pdo->prepare('SELECT id, customer_id, role, is_active FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function setStatus(int $id, bool $active): bool
    {
        $statement = $this->pdo->prepare('UPDATE users SET is_active = ?, session_version = session_version + 1 WHERE id = ?');
        $statement->execute([(int) $active, $id]);
        return $statement->rowCount() === 1;
    }

    public function setRole(int $id, string $role): bool
    {
        $statement = $this->pdo->prepare('UPDATE users SET role = ?, session_version = session_version + 1 WHERE id = ?');
        $statement->execute([$role, $id]);
        return $statement->rowCount() === 1;
    }
}
