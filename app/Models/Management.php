<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Models;

use DomainException;
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
            'staff' => "SELECT COUNT(*) FROM users WHERE role IN ('staff', 'admin', 'super_admin') AND is_active = 1",
            'accounts' => 'SELECT COUNT(*) FROM accounts',
            'frozen_accounts' => "SELECT COUNT(*) FROM accounts WHERE status = 'frozen'",
            'flagged_transactions' => "SELECT COUNT(*) FROM transaction_reviews WHERE status = 'flagged'",
            'pending_ledger' => "SELECT COUNT(*) FROM ledger_requests WHERE status = 'pending'",
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

    /** @return array<int, array<string, mixed>> */
    public function customers(string $search = ''): array
    {
        $like = '%' . $search . '%';
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.display_name, d.identity_type, d.identity_last_four, d.city,
                    d.address_line1, d.postal_code,
                    (SELECT u.email FROM users u WHERE u.customer_id = c.id ORDER BY u.id LIMIT 1) AS email,
                    (SELECT COUNT(*) FROM accounts a WHERE a.customer_id = c.id) AS account_count,
                    (SELECT COUNT(*) FROM users u WHERE u.customer_id = c.id AND u.is_active = 1) AS active_logins
             FROM customers c LEFT JOIN customer_registration_details d ON d.customer_id = c.id
             WHERE (? = \'\' OR c.display_name LIKE ? OR CAST(c.id AS CHAR) LIKE ?)
             ORDER BY c.id DESC LIMIT 100'
        );
        $statement->execute([$search, $like, $like]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function accounts(string $search = ''): array
    {
        $like = '%' . $search . '%';
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.account_number, a.account_type, a.currency, a.balance, a.status,
                    c.id AS customer_id, c.display_name AS customer_name
             FROM accounts a JOIN customers c ON c.id = a.customer_id
             WHERE (? = \'\' OR a.account_number LIKE ? OR c.display_name LIKE ?)
             ORDER BY a.id DESC LIMIT 100'
        );
        $statement->execute([$search, $like, $like]);
        return $statement->fetchAll();
    }

    public function isActiveAccount(int $id): bool
    {
        $statement = $this->pdo->prepare("SELECT id FROM accounts WHERE id = ? AND status = 'active'");
        $statement->execute([$id]);
        return $statement->fetchColumn() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function transactions(string $search = ''): array
    {
        $like = '%' . $search . '%';
        $statement = $this->pdo->prepare(
            'SELECT t.id, t.posted_at, t.description, t.amount, a.account_number, a.currency,
                    c.display_name AS customer_name, r.status AS review_status, r.note AS review_note
             FROM transactions t JOIN accounts a ON a.id = t.account_id
             JOIN customers c ON c.id = a.customer_id
             LEFT JOIN transaction_reviews r ON r.transaction_id = t.id
             WHERE (? = \'\' OR a.account_number LIKE ? OR c.display_name LIKE ? OR t.description LIKE ?)
             ORDER BY t.posted_at DESC, t.id DESC LIMIT 100'
        );
        $statement->execute([$search, $like, $like, $like]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function auditEntries(): array
    {
        return $this->pdo->query(
            'SELECT l.action, l.entity_type, l.entity_id, l.details, l.created_at,
                    COALESCE(u.display_name, \'Former user\') AS actor_name
             FROM management_audit l LEFT JOIN users u ON u.id = l.actor_user_id
             ORDER BY l.id DESC LIMIT 50'
        )->fetchAll();
    }

    public function audit(int $actorId, string $action, string $entityType, int $entityId, string $details = ''): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO management_audit (actor_user_id, action, entity_type, entity_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$actorId, $action, $entityType, $entityId, $details]);
    }

    public function createEmployee(string $name, string $email, string $role, string $password, int $actorId): int
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO users (customer_id, display_name, email, password_hash, role)
                 VALUES (NULL, ?, ?, ?, ?)'
            );
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $id = (int) $this->pdo->lastInsertId();
            $this->audit($actorId, 'user.create', 'user', $id, $role);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function setAccountStatus(int $id, string $status, int $actorId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('UPDATE accounts SET status = ? WHERE id = ? AND status <> ?');
            $statement->execute([$status, $id, $status]);
            $changed = $statement->rowCount() === 1;
            if ($changed) {
                $this->audit($actorId, 'account.' . $status, 'account', $id);
            }
            $this->pdo->commit();
            return $changed;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function reviewTransaction(int $id, string $status, string $note, int $actorId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $exists = $this->pdo->prepare('SELECT id FROM transactions WHERE id = ?');
            $exists->execute([$id]);
            if ($exists->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }
            $statement = $this->pdo->prepare(
                'INSERT INTO transaction_reviews (transaction_id, status, note, reviewed_by)
                 VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status),
                 note = VALUES(note), reviewed_by = VALUES(reviewed_by), reviewed_at = CURRENT_TIMESTAMP'
            );
            $statement->execute([$id, $status, $note, $actorId]);
            $this->audit($actorId, 'transaction.' . $status, 'transaction', $id, $note);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function ledgerRequests(): array
    {
        return $this->pdo->query(
            'SELECT r.id, r.account_id, r.direction, r.amount, r.reason, r.status,
                    r.requested_by, r.created_at, a.account_number, a.currency,
                    c.display_name AS customer_name, u.display_name AS maker_name
             FROM ledger_requests r JOIN accounts a ON a.id = r.account_id
             JOIN customers c ON c.id = a.customer_id
             LEFT JOIN users u ON u.id = r.requested_by
             ORDER BY (r.status = \'pending\') DESC, r.id DESC LIMIT 50'
        )->fetchAll();
    }

    public function openAccount(int $customerId, string $type, int $actorId): string
    {
        $this->pdo->beginTransaction();
        try {
            $customer = $this->pdo->prepare('SELECT id FROM customers WHERE id = ? FOR UPDATE');
            $customer->execute([$customerId]);
            if ($customer->fetchColumn() === false) {
                throw new DomainException('Customer not found.');
            }
            $accountNumber = '241' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $statement = $this->pdo->prepare(
                'INSERT INTO accounts (customer_id, account_number, account_type, currency, balance)
                 VALUES (?, ?, ?, \'LKR\', 0.00)'
            );
            $statement->execute([$customerId, $accountNumber, $type]);
            $this->audit($actorId, 'account.open', 'account', (int) $this->pdo->lastInsertId(), $type);
            $this->pdo->commit();
            return $accountNumber;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function requestLedgerEntry(int $accountId, string $direction, string $amount, string $reason, int $actorId): int
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('INSERT INTO ledger_requests (account_id, direction, amount, reason, requested_by) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$accountId, $direction, $amount, $reason, $actorId]);
            $id = (int) $this->pdo->lastInsertId();
            $this->audit($actorId, 'ledger.request', 'ledger_request', $id, $direction . ' ' . $amount);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function decideLedgerEntry(int $id, string $decision, int $actorId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('SELECT * FROM ledger_requests WHERE id = ? FOR UPDATE');
            $statement->execute([$id]);
            $request = $statement->fetch();
            if ($request === false || $request['status'] !== 'pending') {
                $this->pdo->rollBack();
                return false;
            }
            if ((int) $request['requested_by'] === $actorId) {
                throw new DomainException('A second team member must approve this entry.');
            }
            if ($decision === 'approved') {
                $account = $this->pdo->prepare('SELECT id, balance, status FROM accounts WHERE id = ? FOR UPDATE');
                $account->execute([(int) $request['account_id']]);
                $record = $account->fetch();
                if ($record === false || $record['status'] !== 'active') {
                    throw new DomainException('The account is unavailable for posting.');
                }
                $sign = $request['direction'] === 'credit' ? 1 : -1;
                $change = $sign > 0 ? (string) $request['amount'] : '-' . $request['amount'];
                $balance = $this->pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND balance + ? >= 0');
                $balance->execute([$change, $request['account_id'], $change]);
                if ($balance->rowCount() !== 1) {
                    throw new DomainException('The account has insufficient funds for this debit.');
                }
                $entry = $this->pdo->prepare('INSERT INTO transactions (account_id, posted_at, description, amount) VALUES (?, UTC_TIMESTAMP(), ?, ?)');
                $entry->execute([$request['account_id'], $request['reason'], $change]);
            }
            $update = $this->pdo->prepare('UPDATE ledger_requests SET status = ?, reviewed_by = ?, reviewed_at = UTC_TIMESTAMP() WHERE id = ?');
            $update->execute([$decision, $actorId, $id]);
            $this->audit($actorId, 'ledger.' . $decision, 'ledger_request', $id);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
