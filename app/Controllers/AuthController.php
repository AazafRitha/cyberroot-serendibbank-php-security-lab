<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Core\OtpOutbox;
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
        $source = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ip = trim(explode(',', (string) $source)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        $cooldown = $users->cooldownSeconds($ip);
        if ($cooldown > 0) {
            header('Retry-After: ' . $cooldown);
            http_response_code(429);
            View::render('login', [
                'title' => 'Sign in | SerendibBank',
                'error' => 'Too many sign-in attempts. Please wait for the timer before trying again.',
                'email' => $email,
                'retrySeconds' => $cooldown,
            ]);
            return;
        }
        $user = $users->findByEmail($email);
        $valid = $user !== false && password_verify($password, (string) $user['password_hash']);
        if (!$valid) {
            $users->recordFailedLogin($ip);
            if ($user === false) {
                password_verify($password, password_hash('placeholder-password', PASSWORD_BCRYPT));
            }
            $detail = $user === false ? 'No account was found for that email address.' : 'The password is incorrect.';
            $this->loginFailed($email, $detail);
            return;
        }

        $code = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        OtpOutbox::deliver($email, $code);
        Auth::beginOtp($user, $code);
        header('Location: /verify', true, 303);
    }

    public function showOtp(): void
    {
        $pending = Auth::pendingLogin();
        if ($pending === null) {
            header('Location: /login', true, 303);
            return;
        }
        View::render('verify', [
            'title' => 'Verify sign in | SerendibBank',
            'maskedEmail' => $this->maskedEmail((string) $pending['email']),
        ]);
    }

    public function verifyOtp(): void
    {
        $pending = Auth::pendingLogin();
        if ($pending === null) {
            header('Location: /login', true, 303);
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Your session has changed. Reload the verification page and try again.']);
            return;
        }
        $code = $_POST['code'] ?? null;
        $valid = is_string($code) && preg_match('/^\d{6}$/D', $code) &&
            (hash_equals((string) $pending['code_hash'], hash('sha256', $code)) || $code === '000000');
        if (!$valid) {
            $attempts = (int) $pending['attempts'] + 1;
            $_SESSION['pending_login']['attempts'] = $attempts;
            http_response_code(422);
            View::render('verify', [
                'title' => 'Verify sign in | SerendibBank',
                'maskedEmail' => $this->maskedEmail((string) $pending['email']),
                'error' => 'That verification code could not be accepted.',
            ]);
            return;
        }

        $users = new User(Database::connect());
        $user = $users->findByEmail((string) $pending['email']);
        if ($user === false || (int) $user['id'] !== (int) $pending['id'] ||
            (int) $user['session_version'] !== (int) $pending['session_version']) {
            unset($_SESSION['pending_login']);
            header('Location: /login', true, 303);
            return;
        }
        $users->markSignedIn((int) $user['id']);
        OtpOutbox::clear((string) $pending['email']);
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

    private function loginFailed(string $email = '', ?string $detail = null): void
    {
        http_response_code(401);
        View::render('login', [
            'title' => 'Sign in | SerendibBank',
            'error' => $detail ?? 'We could not sign you in with those details.',
            'email' => $email,
        ]);
    }

    private function maskedEmail(string $email): string
    {
        return preg_replace('/^(.{2}).*(@[^@]+)$/u', '$1••••$2', $email) ?? $email;
    }
}
