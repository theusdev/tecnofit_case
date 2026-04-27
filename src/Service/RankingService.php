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

    public function getRanking(int|string $movementIdentifier, ?int $limit = null): MovementRanking
    {
        if ($limit !== null && $limit <= 0) {
            throw new ValidationException('O limite deve ser um número positivo');
        }

        if (is_int($movementIdentifier)) {
            if ($movementIdentifier <= 0) {
                throw new ValidationException('O ID do movimento deve ser um número positivo');
            }
            $movement = $this->movementRepository->findById($movementIdentifier);
        } else {
            if (trim($movementIdentifier) === '') {
                throw new ValidationException('O nome do movimento não pode ser vazio');
            }
            $movement = $this->movementRepository->findByName($movementIdentifier);
        }

        if ($movement === null) {
            $identifier = is_int($movementIdentifier) ? "ID {$movementIdentifier}" : "nome '{$movementIdentifier}'";
            throw new NotFoundException("Movimento com {$identifier} não encontrado");
        }

        $movementId = $movement['id'];
        $rankingData = $this->personalRecordRepository->getRankingByMovement($movementId, $limit);
        $totalUsers = $this->personalRecordRepository->countUsersByMovement($movementId);

        $entries = [];
        $lastPersonalRecord = null;
        $currentPosition = 0;

        foreach ($rankingData as $index => $row) {
            $personalRecord = (float) $row['value'];

            if ($lastPersonalRecord === null || $personalRecord < $lastPersonalRecord) {
                $currentPosition = $index + 1;
                $lastPersonalRecord = $personalRecord;
            }

            $entries[] = new RankingEntry(
                position: $currentPosition,
                userId: (int) $row['user_id'],
                userName: $row['user_name'],
                personalRecord: $personalRecord,
                recordDate: $row['date']
            );
        }

        return new MovementRanking(
            movementId: $movementId,
            movementName: $movement['name'],
            entries: $entries,
            totalUsers: $totalUsers
        );
    }

    /**
     * @deprecated Use getRanking() instead
     */
    public function getRankingByMovement(int $movementId, ?int $limit = null): MovementRanking
    {
        return $this->getRanking($movementId, $limit);
    }
}
