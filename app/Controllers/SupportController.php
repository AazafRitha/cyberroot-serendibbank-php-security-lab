<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Portal;

final class SupportController
{
    private const CATEGORIES = ['account', 'card', 'digital', 'other'];

    public function show(): void
    {
        $this->render();
    }

    public function submit(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Reload the support page and try again.']);
            return;
        }

        $category = $_POST['category'] ?? null;
        $subject = $_POST['subject'] ?? null;
        $message = $_POST['message'] ?? null;
        if (!is_string($category) || !in_array($category, self::CATEGORIES, true) ||
            !is_string($subject) || strlen(trim($subject)) < 4 || strlen($subject) > 160 ||
            !is_string($message) || strlen(trim($message)) < 10 || strlen($message) > 4000) {
            http_response_code(422);
            $this->render('Check the category, subject and message, then try again.');
            return;
        }

        (new Portal(Database::connect()))->createSupportRequest(
            (int) $user['customer_id'], $category, trim($subject), trim($message)
        );
        $_SESSION['flash_success'] = 'Your message was received. You can follow its status here.';
        header('Location: /dashboard', true, 303);
    }

    private function render(?string $error = null): void
    {
        if (Auth::requireRole('customer') === null) {
            return;
        }
        View::render('support-request', ['title' => 'Contact support | SerendibBank', 'error' => $error]);
    }
}
