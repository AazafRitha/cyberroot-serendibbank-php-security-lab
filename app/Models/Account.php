<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use DomainException;
use PDO;
use Throwable;

final class Account
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forCustomer(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, account_number, account_type, balance, currency
             FROM accounts WHERE customer_id = :customer_id ORDER BY id'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function recentTransactions(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.posted_at, t.description, t.amount, a.currency, a.account_number
             FROM transactions AS t
             INNER JOIN accounts AS a ON a.id = t.account_id
             WHERE a.customer_id = :customer_id
             ORDER BY t.posted_at DESC, t.id DESC LIMIT 12'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    public function transfer(int $customerId, int $fromId, int $toId, string $amount, string $token): string
    {
        $previous = $this->pdo->prepare('SELECT reference FROM transfers WHERE customer_id = ? AND request_token = ?');
        $previous->execute([$customerId, $token]);
        $reference = $previous->fetchColumn();
        if ($reference !== false) {
            return (string) $reference;
        }

        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare(
                'SELECT id, account_number, currency FROM accounts
                 WHERE id IN (?, ?) AND customer_id = ? ORDER BY id FOR UPDATE'
            );
            $lock->execute([$fromId, $toId, $customerId]);
            $rows = $lock->fetchAll();
            if (count($rows) !== 2) {
                throw new DomainException('Choose two of your accounts.');
            }
            $accounts = [];
            foreach ($rows as $row) {
                $accounts[(int) $row['id']] = $row;
            }
            if ($accounts[$fromId]['currency'] !== $accounts[$toId]['currency']) {
                throw new DomainException('Choose accounts with the same currency.');
            }

            $debit = $this->pdo->prepare(
                'UPDATE accounts SET balance = balance - ? WHERE id = ? AND customer_id = ? AND balance >= ?'
            );
            $debit->execute([$amount, $fromId, $customerId, $amount]);
            if ($debit->rowCount() !== 1) {
                throw new DomainException('The available balance is not enough for this transfer.');
            }
            $credit = $this->pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND customer_id = ?');
            $credit->execute([$amount, $toId, $customerId]);
            if ($credit->rowCount() !== 1) {
                throw new DomainException('The receiving account is unavailable.');
            }

            $reference = 'SB' . strtoupper(bin2hex(random_bytes(8)));
            $record = $this->pdo->prepare(
                'INSERT INTO transfers (customer_id, from_account_id, to_account_id, amount, reference, request_token)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $record->execute([$customerId, $fromId, $toId, $amount, $reference, $token]);

            $entry = $this->pdo->prepare(
                'INSERT INTO transactions (account_id, posted_at, description, amount)
                 VALUES (?, UTC_TIMESTAMP(), ?, ?)'
            );
            $entry->execute([$fromId, 'Transfer to ••••' . substr((string) $accounts[$toId]['account_number'], -4), '-' . $amount]);
            $entry->execute([$toId, 'Transfer from ••••' . substr((string) $accounts[$fromId]['account_number'], -4), $amount]);

            $this->pdo->commit();
            return $reference;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function quickTransfer(int $customerId, int $fromId, int $toId, string $amount): string
    {
        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare(
                'SELECT id, account_number, currency FROM accounts
                 WHERE id IN (?, ?) AND customer_id = ? ORDER BY id FOR UPDATE'
            );
            $lock->execute([$fromId, $toId, $customerId]);
            $rows = $lock->fetchAll();
            if (count($rows) !== 2) {
                throw new DomainException('Choose two of your accounts.');
            }
            $accounts = [];
            foreach ($rows as $row) {
                $accounts[(int) $row['id']] = $row;
            }
            if ($accounts[$fromId]['currency'] !== $accounts[$toId]['currency']) {
                throw new DomainException('Choose accounts with the same currency.');
            }

            $debit = $this->pdo->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ? AND balance >= ?');
            $debit->execute([$amount, $fromId, $amount]);
            if ($debit->rowCount() !== 1) {
                throw new DomainException('The available balance is not enough for this transfer.');
            }
            $credit = $this->pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
            $credit->execute([$amount, $toId]);

            $reference = 'SBQ' . strtoupper(bin2hex(random_bytes(8)));
            $record = $this->pdo->prepare(
                'INSERT INTO transfers (customer_id, from_account_id, to_account_id, amount, reference, request_token)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $record->execute([$customerId, $fromId, $toId, number_format(abs((float) $amount), 2, '.', ''), $reference, bin2hex(random_bytes(32))]);

            $entry = $this->pdo->prepare(
                'INSERT INTO transactions (account_id, posted_at, description, amount)
                 VALUES (?, UTC_TIMESTAMP(), ?, ?)'
            );
            $fromChange = number_format(-(float) $amount, 2, '.', '');
            $entry->execute([$fromId, 'Quick transfer to ••••' . substr((string) $accounts[$toId]['account_number'], -4), $fromChange]);
            $entry->execute([$toId, 'Quick transfer from ••••' . substr((string) $accounts[$fromId]['account_number'], -4), $amount]);

            $this->pdo->commit();
            return $reference;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
