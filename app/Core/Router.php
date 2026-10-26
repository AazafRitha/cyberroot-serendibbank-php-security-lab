<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

final class Router
{
    /** @var array<string, callable> */
    private array $getRoutes = [];

    public function get(string $path, callable $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? rtrim($path, '/') : '/';
        $path = $path === '' ? '/' : $path;

        if ($method !== 'GET' || !isset($this->getRoutes[$path])) {
            http_response_code(404);
            View::render('error', ['title' => 'Page not found', 'message' => 'That page does not exist.']);
            return;
        }

        ($this->getRoutes[$path])();
    }
}
