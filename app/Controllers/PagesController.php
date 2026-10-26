<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Core\Lab;

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

    public function offerSearch(): void
    {
        $query = $_GET['q'] ?? '';
        $query = is_string($query) ? trim($query) : '';
        if (strlen($query) > 80) {
            $query = substr($query, 0, 80);
        }
        $offers = [
            ['category' => 'Dining', 'title' => 'Make every meal memorable.', 'description' => 'Dining experiences for evenings out and special occasions.', 'art' => 'dining'],
            ['category' => 'Travel', 'title' => 'A better way to get away.', 'description' => 'Inspiration for stays, journeys and time well spent.', 'art' => 'travel'],
            ['category' => 'Shopping', 'title' => 'Enjoy the little extras.', 'description' => 'More value across everyday essentials and favourite finds.', 'art' => 'shopping'],
            ['category' => 'Wellness', 'title' => 'Time well spent on you.', 'description' => 'Thoughtful ways to make room for wellbeing.', 'art' => 'wellness'],
        ];
        $results = array_values(array_filter($offers, static fn (array $offer): bool =>
            $query === '' || stripos($offer['category'] . ' ' . $offer['title'] . ' ' . $offer['description'], $query) !== false
        ));
        View::render('offer-search', [
            'title' => 'Search card offers | SerendibBank',
            'query' => $query,
            'offers' => $results,
        ]);
    }

    public function digital(): void
    {
        View::render('digital', ['title' => 'Digital Banking | SerendibBank']);
    }

    public function continueToBanking(): void
    {
        $destination = $_GET['next'] ?? '/login';
        if (!is_string($destination) || strlen($destination) > 500 || preg_match('/[\x00-\x1f\x7f]/', $destination)) {
            $destination = '/login';
        }
        $local = str_starts_with($destination, '/') && !str_starts_with($destination, '//');
        $external = preg_match('#^https?://[^/]+(?:/|$)#i', $destination) === 1;
        if (!$local && (!Lab::enabled() || !$external)) {
            $destination = '/login';
        }
        header('Location: ' . $destination, true, 303);
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
