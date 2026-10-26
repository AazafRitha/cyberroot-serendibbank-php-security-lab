<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('home', ['title' => 'CyberRoot SerendibBank']);
    }
}
