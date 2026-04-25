<?php

declare(strict_types=1);

namespace App\Domain;

class RankingEntry
{
    public function __construct(
        public readonly int $position,
        public readonly int $userId,
        public readonly string $userName,
        public readonly float $personalRecord,
        public readonly string $recordDate
    ) {
    }
}
