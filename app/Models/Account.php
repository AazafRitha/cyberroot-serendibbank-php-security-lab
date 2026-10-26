<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use PDO;

final class Account
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forCustomer(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT account_number, account_type, balance, currency
             FROM accounts WHERE customer_id = :customer_id ORDER BY id'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function recentTransactions(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.posted_at, t.description, t.amount, a.currency
             FROM transactions AS t
             INNER JOIN accounts AS a ON a.id = t.account_id
             WHERE a.customer_id = :customer_id
             ORDER BY t.posted_at DESC, t.id DESC LIMIT 8'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }
}
