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
            if (!$request->hasQueryParam('movement_id')) {
                $response->error(
                    'validation_error',
                    'O parâmetro movement_id é obrigatório',
                    400
                );
                return;
            }

            $movementId = (int) $request->getQueryParam('movement_id');
            $limit = $request->hasQueryParam('limit')
                ? (int) $request->getQueryParam('limit')
                : null;

            $ranking = $this->rankingService->getRankingByMovement($movementId, $limit);

            $response->json([
                'data' => [
                    'movement_id' => $ranking->movementId,
                    'movement_name' => $ranking->movementName,
                    'total_users' => $ranking->totalUsers,
                    'ranking' => array_map(
                        fn ($entry) => [
                            'position' => $entry->position,
                            'user_id' => $entry->userId,
                            'user_name' => $entry->userName,
                            'personal_record' => $entry->personalRecord,
                            'record_date' => $entry->recordDate,
                        ],
                        $ranking->entries
                    ),
                ],
            ]);
        } catch (ValidationException $e) {
            $response->error('validation_error', $e->getMessage(), 400);
        } catch (NotFoundException $e) {
            $response->error('not_found', $e->getMessage(), 404);
        } catch (Throwable $e) {
            $response->error(
                'internal_error',
                'Erro ao buscar ranking',
                500
            );
        }
    }
}
