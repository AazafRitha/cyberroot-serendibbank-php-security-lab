<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Core\Lab;
use CyberRoot\SerendibBank\Models\Portal;
use CyberRoot\SerendibBank\Models\Account;

final class ApplicationController
{
    private const PRODUCTS = [
        'account' => ['Everyday account', 'Everyday Plus', 'Regular savings', 'Fixed deposit'],
        'card' => ['Serendib Debit', 'Serendib Credit'],
    ];
    private const EMPLOYMENT = ['employed', 'self-employed', 'student', 'retired', 'other'];

    public function account(): void
    {
        $this->show('account');
    }

    public function card(): void
    {
        $this->show('card');
    }

    public function submitAccount(): void
    {
        $this->submit('account');
    }

    public function submitCard(): void
    {
        $this->submit('card');
    }

    private function show(string $kind, ?string $error = null): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        View::render('application', [
            'title' => ($kind === 'card' ? 'Card application' : 'Account application') . ' | SerendibBank',
            'kind' => $kind,
            'products' => self::PRODUCTS[$kind],
            'employmentOptions' => self::EMPLOYMENT,
            'accounts' => $kind === 'card' ? (new Account(Database::connect()))->forCustomer((int) $user['customer_id']) : [],
            'error' => $error,
        ]);
    }

    private function submit(string $kind): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Reload the application page and try again.']);
            return;
        }

        $product = $_POST['product'] ?? null;
        $employment = $_POST['employment_status'] ?? null;
        $income = $_POST['monthly_income'] ?? null;
        $linkedAccount = filter_var($_POST['linked_account'] ?? null, FILTER_VALIDATE_INT);
        $notes = $_POST['notes'] ?? null;
        $isCredit = $kind === 'card' && $product === 'Serendib Credit';
        $isDebit = $kind === 'card' && $product === 'Serendib Debit';
        if (!is_string($product) || !in_array($product, self::PRODUCTS[$kind], true) ||
            !is_string($notes) || strlen($notes) > 500 ||
            ($isCredit && (!is_string($employment) || !in_array($employment, self::EMPLOYMENT, true))) ||
            ($isCredit && (!is_string($income) || !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $income))) ||
            ($isDebit && (!$linkedAccount || $linkedAccount < 1))) {
            http_response_code(422);
            $this->show($kind, 'Review the form and complete all required fields.');
            return;
        }
        if ($isDebit) {
            $ids = array_map(static fn (array $account): int => (int) $account['id'],
                (new Account(Database::connect()))->forCustomer((int) $user['customer_id']));
            if (!in_array((int) $linkedAccount, $ids, true)) {
                http_response_code(422);
                $this->show($kind, 'Choose one of your accounts for the debit card.');
                return;
            }
        }
        $employment = $isCredit ? $employment : null;
        $income = $isCredit ? $income : null;
        $linkedAccount = $isDebit ? (int) $linkedAccount : null;

        $status = 'pending';
        if (Lab::enabled() && in_array($_POST['status'] ?? null, ['pending', 'reviewed'], true)) {
            // Lab exercise: a client-supplied workflow state reaches the database.
            $status = $_POST['status'];
        }
        (new Portal(Database::connect()))->createApplication(
            (int) $user['customer_id'], $kind, $product, $linkedAccount, $employment, $income, trim($notes), $status
        );
        $_SESSION['flash_success'] = 'Your application was received.';
        header('Location: /dashboard', true, 303);
    }
}
