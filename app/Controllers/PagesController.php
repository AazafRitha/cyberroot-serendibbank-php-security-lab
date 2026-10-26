<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\View;

final class PagesController
{
    public function personal(): void
    {
        View::render('personal', ['title' => 'Personal Banking | SerendibBank']);
    }

    public function cards(): void
    {
        View::render('cards', ['title' => 'Cards | SerendibBank']);
    }

    public function offers(): void
    {
        View::render('offers', ['title' => 'Card Offers | SerendibBank']);
    }

    public function digital(): void
    {
        View::render('digital', ['title' => 'Digital Banking | SerendibBank']);
    }

    public function locations(): void
    {
        View::render('locations', ['title' => 'Branches & ATMs | SerendibBank']);
    }

    public function support(): void
    {
        View::render('support', ['title' => 'Help & Support | SerendibBank']);
    }
}
