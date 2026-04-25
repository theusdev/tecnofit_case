<?php

declare(strict_types=1);

namespace App\Domain;

class MovementRanking
{
    public function __construct(
        public readonly int $movementId,
        public readonly string $movementName,
        public readonly array $entries,
        public readonly int $totalUsers
    ) {
    }
}
