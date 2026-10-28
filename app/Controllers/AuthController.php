<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\Identity;
use CyberRoot\SerendibBank\Core\MailDelivery;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Core\OtpOutbox;
use CyberRoot\SerendibBank\Models\User;
use PDOException;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::user() !== null) {
            header('Location: ' . (Auth::user()['role'] === 'staff' ? '/staff' : '/dashboard'), true, 303);
            return;
        }
        $success = $_SESSION['registration_success'] ?? null;
        $accountNumber = $_SESSION['registration_account_number'] ?? null;
        unset($_SESSION['registration_success'], $_SESSION['registration_account_number']);
        View::render('login', [
            'title' => 'Sign in | SerendibBank',
            'success' => is_string($success) ? $success : null,
            'accountNumber' => is_string($accountNumber) ? $accountNumber : null,
        ]);
    }

    public function showRegister(): void
    {
        if (Auth::user() !== null) {
            header('Location: ' . (Auth::user()['role'] === 'staff' ? '/staff' : '/dashboard'), true, 303);
            return;
        }
        View::render('register', ['title' => 'Register | SerendibBank']);
    }

    public function register(): void
    {
        if (Auth::user() !== null) {
            header('Location: ' . (Auth::user()['role'] === 'staff' ? '/staff' : '/dashboard'), true, 303);
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Your session has changed. Reload the registration page and try again.']);
            return;
        }

        $name = $_POST['name'] ?? null;
        $email = $_POST['email'] ?? null;
        $identityType = $_POST['identity_type'] ?? null;
        $identityNumber = $_POST['identity_number'] ?? null;
        $addressLine1 = $_POST['address_line1'] ?? null;
        $addressLine2 = $_POST['address_line2'] ?? null;
        $city = $_POST['city'] ?? null;
        $postalCode = $_POST['postal_code'] ?? null;
        $password = $_POST['password'] ?? null;
        $confirmation = $_POST['password_confirmation'] ?? null;
        $name = is_string($name) ? trim($name) : '';
        $email = is_string($email) ? strtolower(trim($email)) : '';
        $identityType = is_string($identityType) ? $identityType : '';
        $identityNumber = is_string($identityNumber) ? $identityNumber : '';
        $addressLine1 = is_string($addressLine1) ? trim($addressLine1) : '';
        $addressLine2 = is_string($addressLine2) ? trim($addressLine2) : '';
        $city = is_string($city) ? trim($city) : '';
        $postalCode = is_string($postalCode) ? strtoupper(trim($postalCode)) : '';
        $form = [
            'name' => strlen($name) <= 120 ? $name : '',
            'email' => strlen($email) <= 190 ? $email : '',
            'identityType' => in_array($identityType, ['nic', 'passport'], true) ? $identityType : '',
            'addressLine1' => strlen($addressLine1) <= 160 ? $addressLine1 : '',
            'addressLine2' => strlen($addressLine2) <= 160 ? $addressLine2 : '',
            'city' => strlen($city) <= 80 ? $city : '',
            'postalCode' => strlen($postalCode) <= 12 ? $postalCode : '',
        ];

        $users = new User(Database::connect());
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $cooldown = $users->registrationCooldownSeconds($ip);
        if ($cooldown > 0) {
            header('Retry-After: ' . $cooldown);
            $this->registrationFailed('Too many registration attempts. Please try again later.', $form, 429);
            return;
        }
        $users->recordRegistrationAttempt($ip);

        $normalizedIdentity = Identity::normalize($identityType, $identityNumber);
        if (strlen($name) < 2 || strlen($name) > 120 ||
            strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $normalizedIdentity === null ||
            strlen($addressLine1) < 5 || strlen($addressLine1) > 160 ||
            strlen($addressLine2) > 160 || strlen($city) < 2 || strlen($city) > 80 ||
            !preg_match('/^[A-Z0-9][A-Z0-9 -]{2,11}$/D', $postalCode) ||
            !is_string($password) || !is_string($confirmation) ||
            strlen($password) < 12 || strlen($password) > 256 || $password !== $confirmation) {
            $this->registrationFailed('Check your personal details, address, and matching password of at least 12 characters.', $form);
            return;
        }

        if ($users->findByEmail($email) !== false) {
            $this->registrationFailed('This email address is already registered. Please sign in instead.', $form, 409);
            return;
        }
        $fingerprint = Identity::fingerprint($identityType, $normalizedIdentity);
        if ($users->identityExists($fingerprint)) {
            $this->registrationFailed('This identity document is already registered. Please contact us for help.', $form, 409);
            return;
        }
        try {
            $accountNumber = $users->registerCustomer($name, $email, $password, [
                'identity_type' => $identityType,
                'identity_fingerprint' => $fingerprint,
                'identity_last_four' => substr($normalizedIdentity, -4),
                'address_line1' => $addressLine1,
                'address_line2' => $addressLine2,
                'city' => $city,
                'postal_code' => $postalCode,
            ]);
        } catch (PDOException $exception) {
            $this->registrationFailed('We could not complete registration. Please check your details or contact us.', $form, 409);
            return;
        }

        $_SESSION['registration_success'] = 'Your account is ready. Sign in to continue.';
        $_SESSION['registration_account_number'] = $accountNumber;
        header('Location: /login', true, 303);
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
        $valid = $user !== false && (int) $user['is_active'] === 1 && password_verify($password, (string) $user['password_hash']);
        if (!$valid) {
            $users->recordFailedLogin($ip);
            if ($user === false) {
                password_verify($password, password_hash('placeholder-password', PASSWORD_BCRYPT));
            }
            $detail = $user === false ? 'No account was found for that email address.' : 'The password is incorrect.';
            $this->loginFailed($email, $detail);
            return;
        }

        if ($user['role'] === 'admin') {
            $this->loginFailed($email, 'Use the management workspace for this account.');
            return;
        }

        $code = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        try {
            MailDelivery::sendSignInCode((string) $user['email'], $code);
        } catch (\Throwable $exception) {
            http_response_code(503);
            View::render('login', [
                'title' => 'Sign in | SerendibBank',
                'error' => 'The verification code could not be sent. Please try again later.',
                'email' => $email,
            ]);
            return;
        }
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
        if (!is_string($afterLogin) || !in_array($afterLogin, ['/dashboard', '/settings', '/transfer', '/apply/account', '/apply/card', '/support/request', '/staff'], true) ||
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

    /** @param array<string, string> $form */
    private function registrationFailed(string $message, array $form, int $status = 422): void
    {
        http_response_code($status);
        View::render('register', [
            'title' => 'Register | SerendibBank',
            'error' => $message,
        ] + $form);
    }

    private function maskedEmail(string $email): string
    {
        return preg_replace('/^(.{2}).*(@[^@]+)$/u', '$1••••$2', $email) ?? $email;
    }
}
