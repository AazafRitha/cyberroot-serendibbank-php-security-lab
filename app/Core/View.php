<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

use RuntimeException;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . $template . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException('View does not exist.');
        }

        $escape = static fn (mixed $value): string => htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $data['currentUser'] = Auth::user();
        $data['csrfToken'] = Auth::csrfToken();

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require dirname(__DIR__) . '/Views/layout.php';
    }
}
