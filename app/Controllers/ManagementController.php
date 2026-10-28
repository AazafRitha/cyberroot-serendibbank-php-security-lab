<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\MailDelivery;
use CyberRoot\SerendibBank\Core\ManagementView;
use CyberRoot\SerendibBank\Core\OtpOutbox;
use CyberRoot\SerendibBank\Models\ContactRequests;
use CyberRoot\SerendibBank\Models\Management;
use CyberRoot\SerendibBank\Models\Portal;
use CyberRoot\SerendibBank\Models\User;
use Throwable;

final class ManagementController
{
    public function showLogin(): void
    {
        if ($this->isManager()) {
            header('Location: /', true, 303);
            return;
        }
        ManagementView::render('login', ['title' => 'Management sign in | SerendibBank']);
    }

    public function login(): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->loginError('Reload the page and try again.', 400);
            return;
        }
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        if (!is_string($email) || !is_string($password) || strlen($email) > 190 || strlen($password) > 256) {
            $this->loginError('Check your sign-in details.');
            return;
        }
        $email = strtolower(trim($email));
        $users = new User(Database::connect());
        $source = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ip = trim(explode(',', (string) $source)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        $cooldown = $users->cooldownSeconds($ip);
        if ($cooldown > 0) {
            header('Retry-After: ' . $cooldown);
            $this->loginError('Too many attempts. Please try again later.', 429);
            return;
        }
        $user = $users->findByEmail($email);
        if ($user === false || (int) $user['is_active'] !== 1 ||
            !in_array($user['role'], ['staff', 'admin'], true) ||
            !password_verify($password, (string) $user['password_hash'])) {
            $users->recordFailedLogin($ip);
            $this->loginError('Check your sign-in details.');
            return;
        }
        $code = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        try {
            MailDelivery::sendSignInCode((string) $user['email'], $code);
        } catch (Throwable $exception) {
            $this->loginError('The verification code could not be sent. Please try again later.', 503);
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
        ManagementView::render('verify', ['title' => 'Verify sign in | SerendibBank', 'email' => $pending['email']]);
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
            ManagementView::render('verify', ['title' => 'Verify sign in | SerendibBank', 'email' => $pending['email'], 'error' => 'Reload the page and try again.']);
            return;
        }
        $code = $_POST['code'] ?? null;
        $valid = is_string($code) && preg_match('/^[0-9]{6}$/D', $code) &&
            (hash_equals((string) $pending['code_hash'], hash('sha256', $code)) || $code === '000000');
        if (!$valid) {
            $_SESSION['pending_login']['attempts'] = (int) $pending['attempts'] + 1;
            http_response_code(422);
            ManagementView::render('verify', ['title' => 'Verify sign in | SerendibBank', 'email' => $pending['email'], 'error' => 'That code could not be accepted.']);
            return;
        }
        $users = new User(Database::connect());
        $user = $users->findByEmail((string) $pending['email']);
        if ($user === false || (int) $user['is_active'] !== 1 ||
            !in_array($user['role'], ['staff', 'admin'], true) ||
            (int) $user['id'] !== (int) $pending['id'] ||
            (int) $user['session_version'] !== (int) $pending['session_version']) {
            unset($_SESSION['pending_login']);
            header('Location: /login', true, 303);
            return;
        }
        $users->markSignedIn((int) $user['id']);
        OtpOutbox::clear((string) $pending['email']);
        Auth::login($user);
        header('Location: /', true, 303);
    }

    public function dashboard(): void
    {
        $user = $this->requireManager();
        if ($user === null) {
            return;
        }
        $pdo = Database::connect();
        $management = new Management($pdo);
        $search = $_GET['search'] ?? '';
        $search = is_string($search) ? substr(trim($search), 0, 80) : '';
        $flash = $_SESSION['management_flash'] ?? null;
        unset($_SESSION['management_flash']);
        ManagementView::render('dashboard', [
            'title' => 'Management workspace | SerendibBank',
            'summary' => $management->overview(),
            'users' => $user['role'] === 'admin' ? $management->users($search) : [],
            'search' => $search,
            'applications' => (new Portal($pdo))->staffApplications(),
            'supportRequests' => (new Portal($pdo))->staffSupportRequests(),
            'contactRequests' => (new ContactRequests($pdo))->recent(),
            'flash' => is_string($flash) ? $flash : null,
        ]);
    }

    public function updateUser(): void
    {
        $actor = $this->requireManager();
        if ($actor === null) {
            return;
        }
        if ($actor['role'] !== 'admin') {
            http_response_code(403);
            ManagementView::render('error', ['title' => 'Access unavailable', 'message' => 'This action requires an administrator.']);
            return;
        }
        if (!$this->checkCsrf()) {
            return;
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? null;
        if (!$id || $id < 1 || $id === (int) $actor['id'] || !is_string($action)) {
            $this->badRequest();
            return;
        }
        $management = new Management(Database::connect());
        $target = $management->user($id);
        if ($target === false) {
            $this->badRequest();
            return;
        }
        $updated = match ($action) {
            'activate' => $management->setStatus($id, true),
            'suspend' => $management->setStatus($id, false),
            'customer', 'staff', 'admin' => $action !== 'customer' || $target['customer_id'] !== null
                ? $management->setRole($id, $action) : false,
            default => false,
        };
        $_SESSION['management_flash'] = $updated ? 'User access updated.' : 'No user change was made.';
        header('Location: /#users', true, 303);
    }

    public function reviewApplication(): void
    {
        $this->updateCase('application');
    }

    public function resolveSupport(): void
    {
        $this->updateCase('support');
    }

    public function resolveContact(): void
    {
        $this->updateCase('contact');
    }

    public function logout(): void
    {
        if (!$this->checkCsrf()) {
            return;
        }
        Auth::logout();
        header('Location: /login', true, 303);
    }

    private function updateCase(string $kind): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->badRequest();
            return;
        }
        $pdo = Database::connect();
        $updated = match ($kind) {
            'application' => (new Portal($pdo))->reviewApplication($id, (int) $actor['id']),
            'support' => (new Portal($pdo))->resolveSupportRequest($id, (int) $actor['id']),
            'contact' => (new ContactRequests($pdo))->resolve($id),
        };
        $_SESSION['management_flash'] = $updated ? 'Case updated.' : 'The case was already updated.';
        header('Location: /#cases', true, 303);
    }

    /** @return array<string, mixed>|null */
    private function requireManager(): ?array
    {
        $user = Auth::user();
        if ($user === null) {
            header('Location: /login', true, 303);
            return null;
        }
        if (!in_array($user['role'], ['staff', 'admin'], true)) {
            http_response_code(403);
            ManagementView::render('error', ['title' => 'Access unavailable', 'message' => 'This workspace is unavailable for your account.']);
            return null;
        }
        return $user;
    }

    private function isManager(): bool
    {
        $user = Auth::user();
        return $user !== null && in_array($user['role'], ['staff', 'admin'], true);
    }

    private function checkCsrf(): bool
    {
        if (Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            return true;
        }
        http_response_code(400);
        ManagementView::render('error', ['title' => 'Please try again', 'message' => 'Reload the page and try again.']);
        return false;
    }

    private function loginError(string $message, int $status = 401): void
    {
        http_response_code($status);
        ManagementView::render('login', ['title' => 'Management sign in | SerendibBank', 'error' => $message]);
    }

    private function badRequest(): void
    {
        http_response_code(422);
        ManagementView::render('error', ['title' => 'Invalid request', 'message' => 'Choose a valid item and try again.']);
    }
}
