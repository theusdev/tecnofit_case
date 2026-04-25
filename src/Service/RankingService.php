<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\MovementRanking;
use App\Domain\RankingEntry;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\MovementRepository;
use App\Repository\PersonalRecordRepository;

class RankingService
{
    public function __construct(
        private MovementRepository $movementRepository,
        private PersonalRecordRepository $personalRecordRepository
    ) {
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function listMovements(): array
    {
        return $this->movementRepository->findAll();
    }

    public function getRankingByMovement(int $movementId, ?int $limit = null): MovementRanking
    {
        if ($movementId <= 0) {
            throw new ValidationException('O ID do movimento deve ser um número positivo');
        }

        if ($limit !== null && $limit <= 0) {
            throw new ValidationException('O limite deve ser um número positivo');
        }

        $movement = $this->findMovementById($movementId);

        if ($movement === null) {
            throw new NotFoundException("Movimento com ID {$movementId} não encontrado");
        }

        $rankingData = $this->personalRecordRepository->getRankingByMovement($movementId, $limit);
        $totalUsers = $this->personalRecordRepository->countUsersByMovement($movementId);

        $entries = array_map(
            fn (array $row) => new RankingEntry(
                position: (int) $row['position'],
                userId: (int) $row['user_id'],
                userName: $row['user_name'],
                personalRecord: (float) $row['value'],
                recordDate: $row['date']
            ),
            $rankingData
        );

        return new MovementRanking(
            movementId: $movementId,
            movementName: $movement['name'],
            entries: $entries,
            totalUsers: $totalUsers
        );
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function findMovementById(int $movementId): ?array
    {
        $movements = $this->movementRepository->findAll();

        foreach ($movements as $movement) {
            if ($movement['id'] === $movementId) {
                return $movement;
            }
        }

        return null;
    }
}
