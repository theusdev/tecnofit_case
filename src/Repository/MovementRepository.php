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

    /**
     * @return array{id: int, name: string}|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM movement WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array{id: int, name: string}|null
     */
    public function findByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM movement WHERE name = :name');
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }
}
