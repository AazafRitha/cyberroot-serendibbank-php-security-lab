<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

final class Router
{
    /** @var array<string, callable> */
    private array $getRoutes = [];

    /** @var array<string, callable> */
    private array $postRoutes = [];

    public function get(string $path, callable $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->postRoutes[$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? rtrim($path, '/') : '/';
        $path = $path === '' ? '/' : $path;

        $routes = match ($method) {
            'GET' => $this->getRoutes,
            'POST' => $this->postRoutes,
            default => [],
        };

        if (!isset($routes[$path])) {
            if (isset($this->getRoutes[$path]) || isset($this->postRoutes[$path])) {
                http_response_code(405);
                header('Allow: ' . (isset($this->getRoutes[$path]) ? 'GET, ' : '') . (isset($this->postRoutes[$path]) ? 'POST' : ''));
                View::render('error', ['title' => 'Method not allowed', 'message' => 'Please use the available action for this page.']);
            } else {
                http_response_code(404);
                View::render('error', ['title' => 'Page not found', 'message' => 'That page does not exist.']);
            }
            return;
        }

        ($routes[$path])();
    }
}
