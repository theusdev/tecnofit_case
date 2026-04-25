<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\HealthController;
use App\Controller\RankingController;
use App\Database\Connection;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repository\MovementRepository;
use App\Repository\PersonalRecordRepository;
use App\Service\RankingService;

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Configurar timezone
date_default_timezone_set('UTC');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Dependency Injection Manual
$pdo = Connection::getInstance();
$movementRepository = new MovementRepository($pdo);
$personalRecordRepository = new PersonalRecordRepository($pdo);
$rankingService = new RankingService($movementRepository, $personalRecordRepository);

$healthController = new HealthController();
$rankingController = new RankingController($rankingService);

// Configurar rotas
$router = new Router();
$router->get('/health', [$healthController, 'health']);
$router->get('/movements', [$rankingController, 'listMovements']);
$router->get('/ranking', [$rankingController, 'getRanking']);

// Despachar request
$request = new Request();
$response = new Response();

try {
    $router->dispatch($request, $response);
} catch (Throwable $e) {
    $response->error('internal_error', 'Erro interno do servidor', 500);
}
