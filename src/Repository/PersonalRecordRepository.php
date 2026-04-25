<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class PersonalRecordRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * @return array<int, array{position: int, user_id: int, user_name: string, value: float, date: string}>
     */
    public function getRankingByMovement(int $movementId, ?int $limit = null): array
    {
        $sql = '
            SELECT
                DENSE_RANK() OVER (ORDER BY pr.value DESC) as position,
                u.id as user_id,
                u.name as user_name,
                pr.value,
                DATE_FORMAT(pr.date, "%Y-%m-%d") as date
            FROM personal_record pr
            INNER JOIN user u ON pr.user_id = u.id
            WHERE pr.movement_id = :movement_id
            ORDER BY position ASC, pr.value DESC
        ';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':movement_id', $movementId, PDO::PARAM_INT);

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countUsersByMovement(int $movementId): int
    {
        $sql = '
            SELECT COUNT(DISTINCT user_id) as total
            FROM personal_record
            WHERE movement_id = :movement_id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':movement_id', $movementId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }
}
