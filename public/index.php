<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Controllers\DashboardController;
use CyberRoot\SerendibBank\Controllers\HomeController;
use CyberRoot\SerendibBank\Core\Router;
use CyberRoot\SerendibBank\Core\View;

spl_autoload_register(static function (string $class): void {
    $prefix = 'CyberRoot\\SerendibBank\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$router = new Router();
$router->get('/', [new HomeController(), 'index']);
$router->get('/dashboard', [new DashboardController(), 'index']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (PDOException $exception) {
    error_log((string) $exception);
    http_response_code(503);
    View::render('error', ['title' => 'Service unavailable', 'message' => 'Account information is temporarily unavailable.']);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    View::render('error', ['title' => 'Something went wrong', 'message' => 'Please try again.']);
}
