<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\ManagementView;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\UserSession;

final class SessionController
{
    public function customerIndex(): void
    {
        $user = Auth::requireRole('customer');
        if ($user !== null) {
            $this->render($user, false);
        }
    }

    public function customerRevoke(): void
    {
        $user = Auth::requireRole('customer');
        if ($user !== null) {
            $this->revoke($user, false);
        }
    }

    public function managementIndex(): void
    {
        $user = $this->manager();
        if ($user !== null) {
            $this->render($user, true);
        }
    }

    public function managementRevoke(): void
    {
        $user = $this->manager();
        if ($user !== null) {
            $this->revoke($user, true);
        }
    }

    /** @param array<string, mixed> $user */
    private function render(array $user, bool $management): void
    {
        $flash = $_SESSION['session_flash'] ?? null;
        unset($_SESSION['session_flash']);
        $data = [
            'title' => 'Signed-in devices | SerendibBank',
            'sessions' => (new UserSession(Database::connect()))->forUser((int) $user['id']),
            'currentSessionId' => (int) ($user['session_record_id'] ?? 0),
            'flash' => is_string($flash) ? $flash : null,
        ];
        if ($management) {
            ManagementView::render('sessions', $data);
        } else {
            View::render('sessions', $data);
        }
    }

    /** @param array<string, mixed> $user */
    private function revoke(array $user, bool $management): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            $this->render($user, $management);
            return;
        }
        $sessions = new UserSession(Database::connect());
        $action = $_POST['action'] ?? '';
        $currentId = (int) ($user['session_record_id'] ?? 0);
        if ($action === 'others') {
            $count = $sessions->revokeOthers((int) $user['id'], $currentId);
            $_SESSION['session_flash'] = $count . ' other session' . ($count === 1 ? '' : 's') . ' ended.';
        } elseif ($action === 'revoke') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                http_response_code(422);
                $this->render($user, $management);
                return;
            }
            $changed = $sessions->revoke($id, (int) $user['id']);
            if ($changed && $id === $currentId) {
                Auth::logout();
                header('Location: /login', true, 303);
                return;
            }
            $_SESSION['session_flash'] = $changed ? 'Session ended.' : 'Session was already ended.';
        } else {
            http_response_code(422);
            $this->render($user, $management);
            return;
        }
        header('Location: /sessions', true, 303);
    }

    /** @return array<string, mixed>|null */
    private function manager(): ?array
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
}
