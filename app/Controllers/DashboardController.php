<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Account;

final class DashboardController
{
    private const DEMO_CUSTOMER_ID = 1;

    public function index(): void
    {
        $account = new Account(Database::connect());

        View::render('dashboard', [
            'title' => 'Demo dashboard',
            'accounts' => $account->forCustomer(self::DEMO_CUSTOMER_ID),
            'transactions' => $account->recentTransactions(self::DEMO_CUSTOMER_ID),
        ]);
    }
}
