<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\User;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::user() !== null) {
            header('Location: ' . (Auth::user()['role'] === 'staff' ? '/staff' : '/dashboard'), true, 303);
            return;
        }
        View::render('login', ['title' => 'Sign in | SerendibBank']);
    }

    public function login(): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Your session has changed. Reload the sign-in page and try again.']);
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (!is_string($email) || !is_string($password)) {
            $this->loginFailed();
            return;
        }
        $email = strtolower(trim($email));
        if (strlen($email) > 190 || strlen($password) > 256 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->loginFailed();
            return;
        }

        $users = new User(Database::connect());
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if ($users->isRateLimited($email, $ip)) {
            http_response_code(429);
            View::render('login', ['title' => 'Sign in | SerendibBank', 'error' => 'Please wait a few minutes before trying again.', 'email' => $email]);
            return;
        }

        $user = $users->findByEmail($email);
        $valid = $user !== false && password_verify($password, (string) $user['password_hash']);
        if (!$valid) {
            if ($user === false) {
                password_verify($password, password_hash('placeholder-password', PASSWORD_BCRYPT));
            }
            $users->recordFailedLogin($email, $ip);
            $this->loginFailed($email);
            return;
        }

        $users->clearEmailAttempts($email);
        $users->markSignedIn((int) $user['id']);
        $afterLogin = $_SESSION['after_login'] ?? '/dashboard';
        Auth::login($user);
        if (!is_string($afterLogin) || !in_array($afterLogin, ['/dashboard', '/transfer', '/apply/account', '/apply/card', '/support/request', '/staff'], true) ||
            ($user['role'] === 'staff' && $afterLogin !== '/staff') ||
            ($user['role'] === 'customer' && $afterLogin === '/staff')) {
            $afterLogin = $user['role'] === 'staff' ? '/staff' : '/dashboard';
        }
        header('Location: ' . $afterLogin, true, 303);
    }

    public function logout(): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Your session has changed. Reload the page and try again.']);
            return;
        }
        Auth::logout();
        header('Location: /login', true, 303);
    }

    private function loginFailed(string $email = ''): void
    {
        http_response_code(401);
        View::render('login', [
            'title' => 'Sign in | SerendibBank',
            'error' => 'We could not sign you in with those details.',
            'email' => $email,
        ]);
    }
}
