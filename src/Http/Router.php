<?php

declare(strict_types=1);

namespace App\Http;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        if (!isset($this->routes[$method])) {
            $response->error('not_found', 'Route not found', 404);
            return;
        }

        foreach ($this->routes[$method] as $routePath => $handler) {
            if ($this->matchRoute($routePath, $path)) {
                $handler($request, $response);
                return;
            }
        }

        $response->error('not_found', 'Route not found', 404);
    }

    private function matchRoute(string $routePath, string $requestPath): bool
    {
        return $routePath === $requestPath;
    }
}
