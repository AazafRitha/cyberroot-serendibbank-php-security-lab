<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

use RuntimeException;

final class ManagementView
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): void
    {
        $path = dirname(__DIR__) . '/Views/management-' . $template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Management view does not exist.');
        }
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $data['currentUser'] = Auth::user();
        $data['csrfToken'] = Auth::csrfToken();
        ob_start();
        require $path;
        $content = ob_get_clean();
        require dirname(__DIR__) . '/Views/management-layout.php';
    }
}
