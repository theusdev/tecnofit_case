<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;

class HealthController
{
    public function health(Request $request, Response $response): void
    {
        $response->json([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
