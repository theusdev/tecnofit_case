<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Service\RankingService;
use Throwable;

class RankingController
{
    public function __construct(
        private RankingService $rankingService
    ) {
    }

    public function listMovements(Request $request, Response $response): void
    {
        try {
            $movements = $this->rankingService->listMovements();

            $response->json([
                'data' => $movements,
            ]);
        } catch (Throwable $e) {
            $response->error(
                'internal_error',
                'Erro ao buscar movimentos',
                500
            );
        }
    }

    public function getRanking(Request $request, Response $response): void
    {
        try {
            $hasMovementId = $request->hasQueryParam('movement_id');
            $hasMovementName = $request->hasQueryParam('movement_name');

            // Valida que exatamente um parâmetro está presente (XOR)
            if (!$hasMovementId && !$hasMovementName) {
                $response->error(
                    'parametros_invalidos',
                    'Exatamente um parâmetro é obrigatório: movement_id ou movement_name',
                    400
                );
                return;
            }

            if ($hasMovementId && $hasMovementName) {
                $response->error(
                    'parametros_invalidos',
                    'Exatamente um parâmetro é obrigatório: movement_id ou movement_name',
                    400
                );
                return;
            }

            $movementIdentifier = $hasMovementId
                ? (int) $request->getQueryParam('movement_id')
                : $request->getQueryParam('movement_name');

            $limit = $request->hasQueryParam('limit')
                ? (int) $request->getQueryParam('limit')
                : null;

            $ranking = $this->rankingService->getRanking($movementIdentifier, $limit);

            // Formato JSON conforme especificação do design
            $response->json([
                'data' => [
                    'movement' => [
                        'id' => $ranking->movementId,
                        'name' => $ranking->movementName,
                    ],
                    'ranking' => array_map(
                        fn ($entry) => [
                            'position' => $entry->position,
                            'user' => [
                                'id' => $entry->userId,
                                'name' => $entry->userName,
                            ],
                            'personal_record' => [
                                'value' => $entry->personalRecord,
                                'date' => $entry->recordDate,
                            ],
                        ],
                        $ranking->entries
                    ),
                ],
                'meta' => [
                    'total_users' => $ranking->totalUsers,
                    'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
                ],
            ]);
        } catch (ValidationException $e) {
            $response->error('parametros_invalidos', $e->getMessage(), 400);
        } catch (NotFoundException $e) {
            $response->error('movimento_nao_encontrado', $e->getMessage(), 404);
        } catch (Throwable $e) {
            // Log completo do erro para debugging (não expor detalhes ao cliente)
            error_log('Erro ao buscar ranking: ' . $e->getMessage());
            $response->error(
                'erro_interno',
                'Ocorreu um erro inesperado',
                500
            );
        }
    }
}
