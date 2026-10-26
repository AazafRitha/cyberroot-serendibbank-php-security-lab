<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Account;
use DomainException;
use PDOException;

final class BankingController
{
    public function statement(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        $id = filter_var($_GET['account'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->notFound();
            return;
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT a.id, a.account_number, a.account_type, a.balance, a.currency, c.display_name
                FROM accounts a JOIN customers c ON c.id = a.customer_id WHERE a.id = ?');
        $statement->execute([$id]);
        $account = $statement->fetch();
        if ($account === false) {
            $this->notFound();
            return;
        }

        $items = $pdo->prepare('SELECT posted_at, description, amount FROM transactions WHERE account_id = ? ORDER BY posted_at DESC, id DESC LIMIT 20');
        $items->execute([$id]);
        View::render('statement', [
            'title' => 'Account statement | SerendibBank',
            'account' => $account,
            'transactions' => $items->fetchAll(),
        ]);
    }

    public function branchSearch(): void
    {
        $query = $_GET['q'] ?? '';
        $query = is_string($query) ? trim($query) : '';
        if (strlen($query) > 80) {
            $query = substr($query, 0, 80);
        }

        $rows = [];
        $error = null;
        if ($query !== '') {
            $base = "SELECT city, service_note FROM (
                SELECT 'Colombo' AS city, 'City Centre banking services' AS service_note
                UNION ALL SELECT 'Kandy', 'Central Province banking services'
                UNION ALL SELECT 'Galle', 'Southern Province banking services'
                UNION ALL SELECT 'Jaffna', 'Northern Province banking services'
            ) AS branches WHERE city LIKE ";
            try {
                $pdo = Database::connect();
                $rows = $pdo->query($base . "'%" . $query . "%' ORDER BY city")->fetchAll();
            } catch (PDOException $exception) {
                http_response_code(422);
                $error = 'We could not complete that search. Try another city.';
            }
        }
        View::render('branch-results', [
            'title' => 'Branch search | SerendibBank',
            'query' => $query,
            'branches' => $rows,
            'error' => $error,
        ]);
    }

    public function repeatTransfer(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        $from = filter_var($_GET['from'] ?? null, FILTER_VALIDATE_INT);
        $to = filter_var($_GET['to'] ?? null, FILTER_VALIDATE_INT);
        $amount = $_GET['amount'] ?? null;
        if (!$from || !$to || $from === $to || !is_string($amount) ||
            !preg_match('/^\d{1,7}(?:\.\d{1,2})?$/', $amount) || (float) $amount <= 0 || (float) $amount > 1000000) {
            http_response_code(422);
            View::render('error', ['title' => 'Transfer unavailable', 'message' => 'Check the accounts and amount.']);
            return;
        }
        try {
            $reference = (new Account(Database::connect()))->transfer((int) $user['customer_id'], $from, $to, $amount, bin2hex(random_bytes(32)));
            $_SESSION['flash_success'] = 'Transfer completed. Reference ' . $reference . '.';
            header('Location: /dashboard', true, 303);
        } catch (DomainException $exception) {
            http_response_code(422);
            View::render('error', ['title' => 'Transfer unavailable', 'message' => $exception->getMessage()]);
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('error', ['title' => 'Page not found', 'message' => 'That item could not be found.']);
    }
}
