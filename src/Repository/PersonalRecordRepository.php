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
     * @return array<int, array{user_id: int, user_name: string, value: float, date: string}>
     */
    public function getRankingByMovement(int $movementId, ?int $limit = null): array
    {
        $sql = '
            SELECT
                personal_bests.user_id,
                personal_bests.user_name,
                personal_bests.value,
                personal_bests.date
            FROM (
                SELECT
                    pr.user_id,
                    u.name as user_name,
                    pr.value,
                    DATE_FORMAT(pr.date, "%Y-%m-%dT%H:%i:%sZ") as date,
                    ROW_NUMBER() OVER (
                        PARTITION BY pr.user_id
                        ORDER BY pr.value DESC, pr.date DESC, pr.id DESC
                    ) as rn
                FROM personal_record pr
                INNER JOIN user u ON pr.user_id = u.id
                WHERE pr.movement_id = :movement_id
            ) as personal_bests
            WHERE personal_bests.rn = 1
            ORDER BY personal_bests.value DESC, personal_bests.user_name ASC
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
