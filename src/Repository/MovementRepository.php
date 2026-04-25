<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class MovementRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM movement ORDER BY name');

        return $stmt->fetchAll();
    }
}
