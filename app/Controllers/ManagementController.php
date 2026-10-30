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
use DomainException;
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
            !in_array($user['role'], ['staff', 'admin', 'super_admin'], true) ||
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
        Auth::beginOtp($user, $code, ($_POST['remember_device'] ?? '') === '1');
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
            !in_array($user['role'], ['staff', 'admin', 'super_admin'], true) ||
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
        $searches = [];
        foreach (['customer', 'account', 'transaction', 'user'] as $kind) {
            $value = $_GET[$kind . '_search'] ?? '';
            $searches[$kind] = is_string($value) ? substr(trim($value), 0, 80) : '';
        }
        $flash = $_SESSION['management_flash'] ?? null;
        $newEmployee = $_SESSION['management_new_employee'] ?? null;
        unset($_SESSION['management_flash']);
        unset($_SESSION['management_new_employee']);
        ManagementView::render('dashboard', [
            'title' => 'Management workspace | SerendibBank',
            'summary' => $management->overview(),
            'customers' => $management->customers($searches['customer']),
            'accounts' => $management->accounts($searches['account']),
            'transactions' => $management->transactions($searches['transaction']),
            'ledgerRequests' => $management->ledgerRequests(),
            'users' => in_array($user['role'], ['admin', 'super_admin'], true) ? $management->users($searches['user']) : [],
            'auditEntries' => $user['role'] === 'super_admin' ? $management->auditEntries() : [],
            'searches' => $searches,
            'search' => $searches['user'],
            'applications' => (new Portal($pdo))->staffApplications(),
            'supportRequests' => (new Portal($pdo))->staffSupportRequests(),
            'contactRequests' => (new ContactRequests($pdo))->recent(),
            'flash' => is_string($flash) ? $flash : null,
            'newEmployee' => is_array($newEmployee) ? $newEmployee : null,
        ]);
    }

    public function updateUser(): void
    {
        $actor = $this->requireManager();
        if ($actor === null) {
            return;
        }
        if (!in_array($actor['role'], ['admin', 'super_admin'], true)) {
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
        if ($target['role'] !== 'customer' && $actor['role'] !== 'super_admin') {
            $this->forbidden();
            return;
        }
        if (in_array($action, ['staff', 'admin', 'super_admin'], true) && $actor['role'] !== 'super_admin') {
            $this->forbidden();
            return;
        }
        if (in_array($action, ['customer', 'staff', 'admin', 'super_admin'], true) &&
            $action !== $target['role'] &&
            ($target['customer_id'] === null || $action !== 'customer') &&
            $actor['role'] !== 'super_admin') {
            $this->forbidden();
            return;
        }
        $updated = match ($action) {
            'activate' => $management->setStatus($id, true),
            'suspend' => $management->setStatus($id, false),
            'customer', 'staff', 'admin', 'super_admin' => $action !== 'customer' || $target['customer_id'] !== null
                ? $management->setRole($id, $action) : false,
            default => false,
        };
        if ($updated) {
            $management->audit((int) $actor['id'], 'user.' . $action, 'user', $id);
        }
        $_SESSION['management_flash'] = $updated ? 'User access updated.' : 'No user change was made.';
        header('Location: /#users', true, 303);
    }

    public function createEmployee(): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        if ($actor['role'] !== 'super_admin') {
            $this->forbidden();
            return;
        }
        $name = $_POST['name'] ?? null;
        $email = $_POST['email'] ?? null;
        $role = $_POST['role'] ?? null;
        if (!is_string($name) || !is_string($email) || !is_string($role) ||
            trim($name) === '' || mb_strlen($name) > 120 || strlen($email) > 190 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['staff', 'admin'], true)) {
            $this->badRequest();
            return;
        }
        $email = strtolower(trim($email));
        $management = new Management(Database::connect());
        if ((new User(Database::connect()))->findByEmail($email) !== false) {
            $_SESSION['management_flash'] = 'That email is already registered.';
            header('Location: /#users', true, 303);
            return;
        }
        $password = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $management->createEmployee(trim($name), $email, $role, $password, (int) $actor['id']);
        $_SESSION['management_new_employee'] = ['email' => $email, 'password' => $password];
        $_SESSION['management_flash'] = 'Team member created. Copy the temporary password below now.';
        header('Location: /#users', true, 303);
    }

    public function updateAccount(): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        if (!in_array($actor['role'], ['admin', 'super_admin'], true)) {
            $this->forbidden();
            return;
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? null;
        if (!$id || !in_array($status, ['active', 'frozen'], true)) {
            $this->badRequest();
            return;
        }
        $changed = (new Management(Database::connect()))->setAccountStatus($id, $status, (int) $actor['id']);
        $_SESSION['management_flash'] = $changed ? 'Account status updated.' : 'No account change was made.';
        header('Location: /#accounts', true, 303);
    }

    public function openAccount(): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        if (!in_array($actor['role'], ['admin', 'super_admin'], true)) {
            $this->forbidden();
            return;
        }
        $customerId = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT);
        $type = $_POST['account_type'] ?? null;
        if (!$customerId || !in_array($type, ['Everyday account', 'Savings account'], true)) {
            $this->badRequest();
            return;
        }
        try {
            $number = (new Management(Database::connect()))->openAccount($customerId, $type, (int) $actor['id']);
            $_SESSION['management_flash'] = 'Account ' . $number . ' opened for the customer.';
        } catch (DomainException $exception) {
            $_SESSION['management_flash'] = $exception->getMessage();
        }
        header('Location: /#accounts', true, 303);
    }

    public function requestLedgerEntry(): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
        $direction = $_POST['direction'] ?? null;
        $amount = $_POST['amount'] ?? null;
        $reason = $_POST['reason'] ?? null;
        if (!$accountId || !in_array($direction, ['credit', 'debit'], true) ||
            !is_string($amount) || preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $amount) !== 1 || (float) $amount <= 0 ||
            !is_string($reason) || trim($reason) === '' || mb_strlen($reason) > 160) {
            $this->badRequest();
            return;
        }
        $management = new Management(Database::connect());
        if (!$management->isActiveAccount($accountId)) {
            $this->badRequest();
            return;
        }
        $management->requestLedgerEntry($accountId, $direction, $amount, trim($reason), (int) $actor['id']);
        $_SESSION['management_flash'] = 'Ledger entry submitted for a second team member to review.';
        header('Location: /#ledger', true, 303);
    }

    public function decideLedgerEntry(): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        if (!in_array($actor['role'], ['admin', 'super_admin'], true)) {
            $this->forbidden();
            return;
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? null;
        if (!$id || !in_array($decision, ['approved', 'rejected'], true)) {
            $this->badRequest();
            return;
        }
        try {
            $updated = (new Management(Database::connect()))->decideLedgerEntry($id, $decision, (int) $actor['id']);
            $_SESSION['management_flash'] = $updated ? 'Ledger request ' . $decision . '.' : 'This request has already been reviewed.';
        } catch (DomainException $exception) {
            $_SESSION['management_flash'] = $exception->getMessage();
        }
        header('Location: /#ledger', true, 303);
    }

    public function reviewTransaction(): void
    {
        $actor = $this->requireManager();
        if ($actor === null || !$this->checkCsrf()) {
            return;
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? null;
        $note = $_POST['note'] ?? null;
        if (!$id || !in_array($status, ['flagged', 'cleared'], true) || !is_string($note) ||
            trim($note) === '' || mb_strlen($note) > 250) {
            $this->badRequest();
            return;
        }
        if ($status === 'cleared' && !in_array($actor['role'], ['admin', 'super_admin'], true)) {
            $this->forbidden();
            return;
        }
        $updated = (new Management(Database::connect()))->reviewTransaction($id, $status, trim($note), (int) $actor['id']);
        $_SESSION['management_flash'] = $updated ? 'Transaction review recorded.' : 'Transaction not found.';
        header('Location: /#transactions', true, 303);
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
        if ($updated) {
            (new Management($pdo))->audit((int) $actor['id'], $kind . '.review', $kind, $id);
        }
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
        if (!in_array($user['role'], ['staff', 'admin', 'super_admin'], true)) {
            http_response_code(403);
            ManagementView::render('error', ['title' => 'Access unavailable', 'message' => 'This workspace is unavailable for your account.']);
            return null;
        }
        return $user;
    }

    private function isManager(): bool
    {
        $user = Auth::user();
        return $user !== null && in_array($user['role'], ['staff', 'admin', 'super_admin'], true);
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

    private function forbidden(): void
    {
        http_response_code(403);
        ManagementView::render('error', ['title' => 'Access unavailable', 'message' => 'This action is unavailable for your role.']);
    }
}
