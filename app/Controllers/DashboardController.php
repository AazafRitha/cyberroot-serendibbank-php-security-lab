<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Account;
use CyberRoot\SerendibBank\Models\Portal;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user !== null && $user['role'] === 'staff') {
            header('Location: /staff', true, 303);
            return;
        }
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        $customerId = (int) $user['customer_id'];
        $pdo = Database::connect();
        $account = new Account($pdo);
        $portal = new Portal($pdo);
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);

        View::render('dashboard', [
            'title' => 'My banking | SerendibBank',
            'name' => $user['name'],
            'flash' => is_string($flash) ? $flash : null,
            'accounts' => $account->forCustomer($customerId),
            'transactions' => $account->recentTransactions($customerId),
            'applications' => $portal->applicationsForCustomer($customerId),
            'supportRequests' => $portal->supportForCustomer($customerId),
        ]);
    }
}
