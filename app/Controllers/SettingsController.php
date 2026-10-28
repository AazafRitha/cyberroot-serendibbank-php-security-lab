<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Settings;
use PDOException;

final class SettingsController
{
    public function show(): void
    {
        $user = Auth::requireRole('customer');
        if ($user !== null) {
            $this->render((int) $user['id']);
        }
    }

    public function saveProfile(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null || !$this->checkCsrf()) {
            return;
        }
        $name = $_POST['display_name'] ?? null;
        $email = $_POST['email'] ?? null;
        $role = $_POST['role'] ?? $user['role'];
        if (!is_string($name) || strlen(trim($name)) < 2 || strlen($name) > 120 ||
            !is_string($email) || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
            !is_string($role) || !in_array($role, ['customer', 'staff'], true)) {
            http_response_code(422);
            $this->render((int) $user['id'], 'Check your name and email, then try again.');
            return;
        }
        try {
            (new Settings(Database::connect()))->saveProfile(
                (int) $user['id'], trim($name), strtolower(trim($email)), $role
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            http_response_code(422);
            $this->render((int) $user['id'], 'That email address is already in use.');
            return;
        }
        $_SESSION['flash_success'] = 'Your profile has been updated.';
        header('Location: /dashboard', true, 303);
    }

    public function savePreferences(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null || !$this->checkCsrf()) {
            return;
        }
        $contact = $_POST['preferred_contact'] ?? null;
        if (!is_string($contact) || !in_array($contact, ['email', 'phone'], true)) {
            http_response_code(422);
            $this->render((int) $user['id'], 'Choose a preferred contact method.');
            return;
        }
        (new Settings(Database::connect()))->savePreferences(
            (int) $user['id'], isset($_POST['account_alerts']), isset($_POST['product_updates']), $contact
        );
        $_SESSION['flash_success'] = 'Your communication preferences have been updated.';
        header('Location: /settings#preferences', true, 303);
    }

    public function changePassword(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null || !$this->checkCsrf()) {
            return;
        }
        $current = $_POST['current_password'] ?? null;
        $new = $_POST['new_password'] ?? null;
        $confirm = $_POST['confirm_password'] ?? null;
        $target = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $settings = new Settings(Database::connect());
        $actor = $settings->user((int) $user['id']);
        if (!is_string($current) || $actor === false || !password_verify($current, (string) $actor['password_hash']) ||
            !is_string($new) || strlen($new) < 12 || strlen($new) > 128 ||
            !is_string($confirm) || !hash_equals($new, $confirm) || !$target || $target < 1) {
            http_response_code(422);
            $this->render((int) $user['id'], 'Check your current password and use a matching new password of at least 12 characters.');
            return;
        }
        if (!$settings->changePassword($target, password_hash($new, PASSWORD_DEFAULT))) {
            http_response_code(422);
            $this->render((int) $user['id'], 'Password could not be updated.');
            return;
        }
        if ($target === (int) $user['id']) {
            $_SESSION['auth']['session_version'] = (int) $user['session_version'] + 1;
        }
        $_SESSION['flash_success'] = 'Your password has been updated.';
        header('Location: /settings#security', true, 303);
    }

    private function checkCsrf(): bool
    {
        if (Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            return true;
        }
        http_response_code(400);
        View::render('error', ['title' => 'Please try again', 'message' => 'Reload account settings and try again.']);
        return false;
    }

    private function render(int $userId, ?string $error = null): void
    {
        $settings = new Settings(Database::connect());
        $profile = $settings->user($userId);
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        View::render('settings', [
            'title' => 'Account settings | SerendibBank',
            'profile' => $profile,
            'registrationDetails' => $profile !== false && $profile['customer_id'] !== null
                ? $settings->registrationDetails((int) $profile['customer_id']) : false,
            'preferences' => $settings->preferences($userId),
            'flash' => is_string($flash) ? $flash : null,
            'error' => $error,
        ]);
    }
}
