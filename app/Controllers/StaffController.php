<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Portal;

final class StaffController
{
    public function index(): void
    {
        $user = Auth::requireRole('staff');
        if ($user === null) {
            return;
        }
        $portal = new Portal(Database::connect());
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $requests = $portal->staffSupportRequests();
        foreach ($requests as &$request) {
            $matches = glob(dirname(__DIR__, 2) . '/public/uploads/request-' . (int) $request['id'] . '-*');
            $request['attachment_url'] = $matches ? '/uploads/' . rawurlencode(basename($matches[0])) : null;
        }
        unset($request);
        View::render('staff-dashboard', [
            'title' => 'Service workspace | SerendibBank',
            'name' => $user['name'],
            'flash' => is_string($flash) ? $flash : null,
            'applications' => $portal->staffApplications(),
            'supportRequests' => $requests,
        ]);
    }

    public function reviewApplication(): void
    {
        $this->update('application');
    }

    public function resolveSupport(): void
    {
        $this->update('support');
    }

    private function update(string $kind): void
    {
        $user = Auth::requireRole('staff');
        if ($user === null) {
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Reload the workspace and try again.']);
            return;
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            http_response_code(422);
            View::render('error', ['title' => 'Invalid request', 'message' => 'Select a valid request.']);
            return;
        }
        $portal = new Portal(Database::connect());
        $updated = $kind === 'application'
            ? $portal->reviewApplication($id, (int) $user['id'])
            : $portal->resolveSupportRequest($id, (int) $user['id']);
        $_SESSION['flash_success'] = $updated ? 'Status updated.' : 'This item was already updated.';
        header('Location: /staff', true, 303);
    }
}
