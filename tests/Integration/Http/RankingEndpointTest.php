<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Controller\HealthController;
use App\Controller\RankingController;
use App\Database\Connection;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repository\MovementRepository;
use App\Repository\PersonalRecordRepository;
use App\Service\RankingService;
use PHPUnit\Framework\TestCase;

class RankingEndpointTest extends TestCase
{
    private Router $router;
    private Response $response;

    protected function setUp(): void
    {
        Connection::resetInstance();
        $pdo = Connection::getInstance();

        $movementRepository = new MovementRepository($pdo);
        $personalRecordRepository = new PersonalRecordRepository($pdo);
        $rankingService = new RankingService($movementRepository, $personalRecordRepository);

        $healthController = new HealthController();
        $rankingController = new RankingController($rankingService);

        $this->router = new Router();
        $this->router->get('/health', [$healthController, 'health']);
        $this->router->get('/movements', [$rankingController, 'listMovements']);
        $this->router->get('/api/rankings', [$rankingController, 'getRanking']);

        $this->response = new Response();
    }

    protected function tearDown(): void
    {
        Connection::resetInstance();
    }

    public function testHealthEndpointReturnsOk(): void
    {
        $request = new Request([], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/health']);

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function testRankingEndpointWithMovementIdReturnsCorrectFormat(): void
    {
        $request = new Request(
            ['movement_id' => '1'],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/rankings?movement_id=1']
        );

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        // Validar estrutura conforme design
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);

        // Validar data.movement
        $this->assertArrayHasKey('movement', $data['data']);
        $this->assertArrayHasKey('id', $data['data']['movement']);
        $this->assertArrayHasKey('name', $data['data']['movement']);

        // Validar data.ranking
        $this->assertArrayHasKey('ranking', $data['data']);
        $this->assertIsArray($data['data']['ranking']);

        if (!empty($data['data']['ranking'])) {
            $entry = $data['data']['ranking'][0];
            $this->assertArrayHasKey('position', $entry);
            $this->assertArrayHasKey('user', $entry);
            $this->assertArrayHasKey('personal_record', $entry);

            $this->assertArrayHasKey('id', $entry['user']);
            $this->assertArrayHasKey('name', $entry['user']);

            $this->assertArrayHasKey('value', $entry['personal_record']);
            $this->assertArrayHasKey('date', $entry['personal_record']);

            // Validar formato ISO 8601 da data
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
                $entry['personal_record']['date'],
                'Data deve estar no formato ISO 8601'
            );
        }

        // Validar meta
        $this->assertArrayHasKey('total_users', $data['meta']);
        $this->assertArrayHasKey('generated_at', $data['meta']);

        // Validar formato ISO 8601 do generated_at
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            $data['meta']['generated_at'],
            'generated_at deve estar no formato ISO 8601'
        );
    }

    public function testRankingEndpointWithMovementNameReturnsCorrectFormat(): void
    {
        $request = new Request(
            ['movement_name' => 'Deadlift'],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/rankings?movement_name=Deadlift']
        );

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('Deadlift', $data['data']['movement']['name']);
    }

    public function testRankingEndpointWithoutParametersReturns400(): void
    {
        $request = new Request(
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/rankings']
        );

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('parametros_invalidos', $data['error']['code']);
        $this->assertStringContainsString('Exatamente um parâmetro', $data['error']['message']);
    }

    public function testRankingEndpointWithBothParametersReturns400(): void
    {
        $request = new Request(
            ['movement_id' => '1', 'movement_name' => 'Deadlift'],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/rankings?movement_id=1&movement_name=Deadlift']
        );

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('parametros_invalidos', $data['error']['code']);
    }

    public function testRankingEndpointWithNonExistentIdReturns404(): void
    {
        $request = new Request(
            ['movement_id' => '999'],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/rankings?movement_id=999']
        );

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('movimento_nao_encontrado', $data['error']['code']);
    }

    public function testRankingEndpointWithNonExistentNameReturns404(): void
    {
        $request = new Request(
            ['movement_name' => 'Inexistente'],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/rankings?movement_name=Inexistente']
        );

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('movimento_nao_encontrado', $data['error']['code']);
    }

    public function testMovementsEndpointReturnsArray(): void
    {
        $request = new Request([], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/movements']);

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);
    }

    public function testNonExistentRouteReturns404(): void
    {
        $request = new Request([], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/invalid-route']);

        ob_start();
        $this->router->dispatch($request, $this->response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('not_found', $data['error']['code']);
    }
}
