<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class Portal
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createApplication(
        int $customerId,
        string $kind,
        string $product,
        ?int $linkedAccountId,
        ?string $employment,
        ?string $income,
        ?string $notes
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO applications (customer_id, kind, product, linked_account_id, employment_status, monthly_income, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$customerId, $kind, $product, $linkedAccountId, $employment, $income, $notes]);
    }

    public function createSupportRequest(int $customerId, string $category, string $subject, string $message): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO support_requests (customer_id, category, subject, message) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$customerId, $category, $subject, $message]);
    }

    /** @return array<int, array<string, mixed>> */
    public function applicationsForCustomer(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, kind, product, status, created_at FROM applications
             WHERE customer_id = ? ORDER BY created_at DESC, id DESC LIMIT 10'
        );
        $statement->execute([$customerId]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function supportForCustomer(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, category, subject, status, created_at FROM support_requests
             WHERE customer_id = ? ORDER BY created_at DESC, id DESC LIMIT 10'
        );
        $statement->execute([$customerId]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function staffApplications(): array
    {
        return $this->pdo->query(
            'SELECT a.id, a.kind, a.product, a.employment_status, a.monthly_income, a.notes,
                    linked.account_number AS linked_account_number,
                    a.status, a.created_at, c.display_name AS customer_name
             FROM applications a JOIN customers c ON c.id = a.customer_id
             LEFT JOIN accounts linked ON linked.id = a.linked_account_id
             ORDER BY a.status = \'pending\' DESC, a.created_at DESC, a.id DESC LIMIT 50'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function staffSupportRequests(): array
    {
        return $this->pdo->query(
            'SELECT s.id, s.category, s.subject, s.message, s.status, s.created_at,
                    c.display_name AS customer_name
             FROM support_requests s JOIN customers c ON c.id = s.customer_id
             ORDER BY s.status = \'open\' DESC, s.created_at DESC, s.id DESC LIMIT 50'
        )->fetchAll();
    }

    public function reviewApplication(int $id, int $staffUserId): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE applications SET status = 'reviewed', reviewed_at = UTC_TIMESTAMP(), reviewed_by = ?
             WHERE id = ? AND status = 'pending'"
        );
        $statement->execute([$staffUserId, $id]);
        return $statement->rowCount() === 1;
    }

    public function resolveSupportRequest(int $id, int $staffUserId): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE support_requests SET status = 'resolved', resolved_at = UTC_TIMESTAMP(), resolved_by = ?
             WHERE id = ? AND status = 'open'"
        );
        $statement->execute([$staffUserId, $id]);
        return $statement->rowCount() === 1;
    }
}
