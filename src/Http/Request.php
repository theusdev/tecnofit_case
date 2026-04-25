<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    public function __construct(
        private array $query = [],
        private array $server = []
    ) {
        $this->query = $query ?: $_GET;
        $this->server = $server ?: $_SERVER;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    public function hasQueryParam(string $key): bool
    {
        return isset($this->query[$key]);
    }
}
