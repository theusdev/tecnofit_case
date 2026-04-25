<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    public function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function error(string $code, string $message, int $statusCode = 400): void
    {
        $this->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }
}
