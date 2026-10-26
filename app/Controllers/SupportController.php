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

        $attachment = $_FILES['attachment'] ?? null;
        $hasAttachment = is_array($attachment) && ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $extension = '';
        if ($hasAttachment) {
            $filename = $attachment['name'] ?? null;
            $size = $attachment['size'] ?? null;
            $extension = is_string($filename) ? strtolower((string) pathinfo($filename, PATHINFO_EXTENSION)) : '';
            if (($attachment['error'] ?? null) !== UPLOAD_ERR_OK || !is_int($size) || $size > 2_000_000 ||
                !in_array($extension, ['png', 'jpg', 'jpeg', 'pdf', 'svg'], true)) {
                http_response_code(422);
                $this->render('Choose a PNG, JPG or PDF attachment under 2 MB.');
                return;
            }
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $id = (new Portal($pdo))->createSupportRequest(
                (int) $user['customer_id'], $category, trim($subject), trim($message)
            );
            if ($hasAttachment) {
                $path = dirname(__DIR__, 2) . '/public/uploads/request-' . $id . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
                if (!move_uploaded_file((string) $attachment['tmp_name'], $path)) {
                    throw new \RuntimeException('Attachment could not be saved.');
                }
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log((string) $exception);
            http_response_code(503);
            $this->render('Your message could not be sent. Please try again.');
            return;
        }
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
