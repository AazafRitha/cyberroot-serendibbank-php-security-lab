<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Account;

final class DashboardController
{
    private const DEFAULT_CUSTOMER_ID = 1;

    public function index(): void
    {
        $account = new Account(Database::connect());

        View::render('dashboard', [
            'title' => 'Accounts | SerendibBank',
            'accounts' => $account->forCustomer(self::DEFAULT_CUSTOMER_ID),
            'transactions' => $account->recentTransactions(self::DEFAULT_CUSTOMER_ID),
        ]);
    }
}
