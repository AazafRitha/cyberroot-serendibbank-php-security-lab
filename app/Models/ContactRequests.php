<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class ContactRequests
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $name, string $email, string $category, string $subject, string $message): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO contact_requests (name, email, category, subject, message) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$name, $email, $category, $subject, $message]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|false */
    public function byReference(string $reference): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, category, subject, message, status, created_at FROM contact_requests WHERE id = ? LIMIT 1'
        );
        $statement->execute([(int) substr($reference, 3)]);
        return $statement->fetch();
    }

    /** @return array<int, array<string, mixed>> */
    public function recent(): array
    {
        return $this->pdo->query(
            "SELECT id, name, email, category, subject, message, status, created_at FROM contact_requests
             ORDER BY status = 'open' DESC, created_at DESC, id DESC LIMIT 40"
        )->fetchAll();
    }

    public function resolve(int $id): bool
    {
        $statement = $this->pdo->prepare("UPDATE contact_requests SET status = 'resolved' WHERE id = ? AND status = 'open'");
        $statement->execute([$id]);
        return $statement->rowCount() === 1;
    }
}
