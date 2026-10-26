<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\ContactRequests;

final class ContactController
{
    private const CATEGORIES = ['accounts', 'cards', 'digital', 'branches', 'other'];

    public function show(): void
    {
        View::render('contact', ['title' => 'Contact us | SerendibBank']);
    }

    public function submit(): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            $this->showError('Reload the form and try again.');
            return;
        }
        $name = $_POST['name'] ?? null;
        $email = $_POST['email'] ?? null;
        $category = $_POST['category'] ?? null;
        $subject = $_POST['subject'] ?? null;
        $message = $_POST['message'] ?? null;
        if (!is_string($name) || strlen(trim($name)) < 2 || strlen($name) > 120 ||
            !is_string($email) || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
            !is_string($category) || !in_array($category, self::CATEGORIES, true) ||
            !is_string($subject) || strlen(trim($subject)) < 4 || strlen($subject) > 160 ||
            !is_string($message) || strlen(trim($message)) < 10 || strlen($message) > 4000) {
            http_response_code(422);
            $this->showError('Check the details and try again.');
            return;
        }
        $id = (new ContactRequests(Database::connect()))->create(
            trim($name), strtolower(trim($email)), $category, trim($subject), trim($message)
        );
        header('Location: /support/track?ref=' . sprintf('SBH%06d', $id), true, 303);
    }

    public function track(): void
    {
        $reference = $_GET['ref'] ?? '';
        $request = false;
        if (is_string($reference) && preg_match('/^SBH[0-9]{6,12}$/', $reference)) {
            $request = (new ContactRequests(Database::connect()))->byReference($reference);
        }
        if ($request === false) {
            http_response_code(404);
        }
        View::render('contact-track', [
            'title' => 'Enquiry status | SerendibBank',
            'reference' => is_string($reference) ? $reference : '',
            'request' => $request,
        ]);
    }

    private function showError(string $error): void
    {
        View::render('contact', ['title' => 'Contact us | SerendibBank', 'error' => $error]);
    }
}
