<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Account;
use DomainException;

final class TransferController
{
    public function show(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        $this->render((int) $user['customer_id']);
    }

    public function submit(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Reload the transfer page and try again.']);
            return;
        }
        $from = filter_var($_POST['from_account'] ?? null, FILTER_VALIDATE_INT);
        $to = filter_var($_POST['to_account'] ?? null, FILTER_VALIDATE_INT);
        $amount = $_POST['amount'] ?? null;
        $token = $_POST['request_token'] ?? null;
        if (!$from || !$to || $from === $to || !is_string($amount) ||
            !preg_match('/^\d{1,7}(?:\.\d{1,2})?$/', $amount) ||
            (float) $amount <= 0 || (float) $amount > 1000000 ||
            !is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token) ||
            !isset($_SESSION['transfer_token']) || !hash_equals($_SESSION['transfer_token'], $token)) {
            http_response_code(422);
            $this->render((int) $user['customer_id'], 'Check the accounts and enter an amount between LKR 0.01 and 1,000,000.00.');
            return;
        }

        try {
            $reference = (new Account(Database::connect()))->transfer(
                (int) $user['customer_id'], $from, $to, $amount, $token
            );
            unset($_SESSION['transfer_token']);
            $_SESSION['flash_success'] = 'Transfer completed. Reference ' . $reference . '.';
            header('Location: /dashboard', true, 303);
        } catch (DomainException $exception) {
            http_response_code(422);
            $this->render((int) $user['customer_id'], $exception->getMessage());
        }
    }

    private function render(int $customerId, ?string $error = null): void
    {
        if (!isset($_SESSION['transfer_token'])) {
            $_SESSION['transfer_token'] = bin2hex(random_bytes(32));
        }
        View::render('transfer', [
            'title' => 'Transfer between accounts | SerendibBank',
            'accounts' => (new Account(Database::connect()))->forCustomer($customerId),
            'requestToken' => $_SESSION['transfer_token'],
            'error' => $error,
        ]);
    }
}
