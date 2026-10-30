<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

use CyberRoot\SerendibBank\Models\UserSession;

final class Auth
{
    private const IDLE_SECONDS = 1800;
    private const MAX_SECONDS = 28800;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (getenv('APP_HTTPS') === '1') ||
            (($_SERVER['HTTPS'] ?? '') === 'on') ||
            (($_SERVER['SERVER_PORT'] ?? '') === '443');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        session_name('serendib_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $auth = $_SESSION['auth'] ?? null;
        if (is_array($auth)) {
            $now = time();
            $idle = $now - (int) ($auth['last_seen'] ?? 0);
            $age = $now - (int) ($auth['signed_in_at'] ?? 0);
            if ($idle > self::IDLE_SECONDS || $age > self::MAX_SECONDS) {
                self::logout();
                session_id('');
                session_start();
            } else {
                $statement = Database::connect()->prepare(
                    'SELECT customer_id, display_name, role, is_active, session_version FROM users WHERE id = :id LIMIT 1'
                );
                $statement->execute(['id' => (int) ($auth['id'] ?? 0)]);
                $record = $statement->fetch();
                if ($record === false || (int) $record['is_active'] !== 1 ||
                    (int) $record['session_version'] !== (int) ($auth['session_version'] ?? 0)) {
                    self::logout();
                    session_id('');
                    session_start();
                } else {
                    $sessions = new UserSession(Database::connect());
                    $sessionId = (int) ($auth['session_record_id'] ?? 0);
                    $session = $sessions->find($sessionId, (int) $auth['id'], session_id());
                    // A remembered-device cookie takes precedence over a remote revocation.
                    if ($session === false || ($session['revoked_at'] !== null && ($_COOKIE['sb_device_trust'] ?? '') !== '1')) {
                        self::logout();
                        session_id('');
                        session_start();
                    } else {
                        $sessions->touch($sessionId);
                        $_SESSION['auth']['last_seen'] = $now;
                        $_SESSION['auth']['customer_id'] = $record['customer_id'] === null ? null : (int) $record['customer_id'];
                        $_SESSION['auth']['name'] = (string) $record['display_name'];
                        $_SESSION['auth']['role'] = (string) $record['role'];
                    }
                }
            }
        }
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        return isset($_SESSION['auth']) && is_array($_SESSION['auth']) ? $_SESSION['auth'] : null;
    }

    /** @param array<string, mixed> $user */
    public static function beginOtp(array $user, string $code, bool $rememberDevice = false): void
    {
        $afterLogin = $_SESSION['after_login'] ?? null;
        session_regenerate_id(true);
        $_SESSION = [];
        if (is_string($afterLogin)) {
            $_SESSION['after_login'] = $afterLogin;
        }
        $_SESSION['pending_login'] = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'session_version' => (int) $user['session_version'],
            'code_hash' => hash('sha256', $code),
            'expires_at' => time() + 300,
            'attempts' => 0,
            'remember_device' => $rememberDevice,
        ];
        self::csrfToken();
    }

    /** @return array<string, mixed>|null */
    public static function pendingLogin(): ?array
    {
        $pending = $_SESSION['pending_login'] ?? null;
        if (!is_array($pending) || (int) ($pending['expires_at'] ?? 0) < time()) {
            unset($_SESSION['pending_login']);
            return null;
        }
        return $pending;
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        $rememberDevice = !empty($_SESSION['pending_login']['remember_device']);
        session_regenerate_id(true);
        $_SESSION = [];
        $sessions = new UserSession(Database::connect());
        $recordId = $sessions->create(
            (int) $user['id'], session_id(),
            (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown browser')
        );
        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'customer_id' => $user['customer_id'] === null ? null : (int) $user['customer_id'],
            'name' => (string) $user['display_name'],
            'role' => (string) $user['role'],
            'session_version' => (int) $user['session_version'],
            'signed_in_at' => time(),
            'last_seen' => time(),
            'session_record_id' => $recordId,
        ];
        self::deviceCookie($rememberDevice);
        self::csrfToken();
    }

    public static function logout(): void
    {
        $auth = $_SESSION['auth'] ?? null;
        if (is_array($auth) && isset($auth['session_record_id'], $auth['id'])) {
            (new UserSession(Database::connect()))->revoke((int) $auth['session_record_id'], (int) $auth['id']);
        }
        $_SESSION = [];
        self::deviceCookie(false);
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }
        session_destroy();
    }

    private static function deviceCookie(bool $remember): void
    {
        $params = session_get_cookie_params();
        setcookie('sb_device_trust', $remember ? '1' : '', [
            'expires' => $remember ? time() + 30 * 86400 : time() - 3600,
            'path' => '/',
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(mixed $token): bool
    {
        return is_string($token) &&
            isset($_SESSION['csrf']) &&
            is_string($_SESSION['csrf']) &&
            hash_equals($_SESSION['csrf'], $token);
    }

    /** @return array<string, mixed>|null */
    public static function requireRole(string $role): ?array
    {
        $user = self::user();
        if ($user === null) {
            $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            if (is_string($path) && in_array($path, ['/dashboard', '/settings', '/transfer', '/apply/account', '/apply/card', '/support/request', '/staff'], true)) {
                $_SESSION['after_login'] = $path;
            }
            header('Location: /login', true, 303);
            return null;
        }
        if ($user['role'] !== $role) {
            http_response_code(403);
            View::render('error', ['title' => 'Access unavailable', 'message' => 'You cannot access this page.']);
            return null;
        }
        return $user;
    }
}
