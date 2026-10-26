<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Models\Portal;

final class ApiController
{
    public function accountTransactions(): void
    {
        if ($this->customer() === null) {
            return;
        }
        $accountId = filter_var($_GET['account_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$accountId || $accountId < 1) {
            $this->json(['error' => 'Choose an account.'], 422);
            return;
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'SELECT a.id, a.account_number, a.account_type, a.currency, a.balance,
                    c.display_name AS account_holder
             FROM accounts a JOIN customers c ON c.id = a.customer_id WHERE a.id = ? LIMIT 1'
        );
        $statement->execute([$accountId]);
        $account = $statement->fetch();
        if ($account === false) {
            $this->json(['error' => 'Account not found.'], 404);
            return;
        }
        $activity = $pdo->prepare(
            'SELECT id, posted_at, description, amount FROM transactions
             WHERE account_id = ? ORDER BY posted_at DESC, id DESC LIMIT 50'
        );
        $activity->execute([$accountId]);
        $this->json(['account' => $account, 'transactions' => $activity->fetchAll()]);
    }

    public function supportRequest(): void
    {
        if ($this->customer() === null) {
            return;
        }
        $requestId = filter_var($_GET['request_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$requestId || $requestId < 1) {
            $this->json(['error' => 'Choose a request.'], 422);
            return;
        }
        $statement = Database::connect()->prepare(
            'SELECT s.id, s.category, s.subject, s.message, s.status, s.created_at,
                    c.display_name AS customer_name
             FROM support_requests s JOIN customers c ON c.id = s.customer_id
             WHERE s.id = ? LIMIT 1'
        );
        $statement->execute([$requestId]);
        $request = $statement->fetch();
        $this->json($request === false ? ['error' => 'Request not found.'] : ['request' => $request], $request === false ? 404 : 200);
    }

    public function reviewApplication(): void
    {
        $user = $this->customer();
        if ($user === null) {
            return;
        }
        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->json(['error' => 'Send a JSON request.'], 400);
            return;
        }
        $id = filter_var($input['application_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->json(['error' => 'Choose an application.'], 422);
            return;
        }
        $updated = (new Portal(Database::connect()))->reviewApplication($id, (int) $user['id']);
        $this->json(['application_id' => $id, 'status' => $updated ? 'reviewed' : 'unchanged']);
    }

    public function beneficiaryLookup(): void
    {
        $accountNumber = $_GET['account_number'] ?? null;
        if (!is_string($accountNumber) || !preg_match('/^\d{8,32}$/D', $accountNumber)) {
            $this->json(['error' => 'Enter an account number.'], 422);
            return;
        }
        $statement = Database::connect()->prepare(
            'SELECT a.id, a.account_number, a.account_type, a.currency, a.balance,
                    c.id AS customer_id, c.display_name AS account_holder
             FROM accounts a JOIN customers c ON c.id = a.customer_id
             WHERE a.account_number = ? LIMIT 1'
        );
        $statement->execute([$accountNumber]);
        $beneficiary = $statement->fetch();
        $this->json($beneficiary === false ? ['error' => 'Account not found.'] : ['beneficiary' => $beneficiary], $beneficiary === false ? 404 : 200);
    }

    /** @return array<string, mixed>|null */
    private function customer(): ?array
    {
        $user = Auth::user();
        if ($user === null) {
            $this->json(['error' => 'Sign in required.'], 401);
            return null;
        }
        if ($user['role'] !== 'customer') {
            $this->json(['error' => 'Access unavailable.'], 403);
            return null;
        }
        return $user;
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
