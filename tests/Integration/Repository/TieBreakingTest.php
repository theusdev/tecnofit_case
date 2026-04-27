<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Database\Connection;
use App\Repository\PersonalRecordRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class TieBreakingTest extends TestCase
{
    private PDO $pdo;
    private PersonalRecordRepository $repository;

    protected function setUp(): void
    {
        Connection::resetInstance();
        $this->pdo = Connection::getInstance();
        $this->repository = new PersonalRecordRepository($this->pdo);

        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        Connection::resetInstance();
    }

    private function setupTestData(): void
    {
        // Criar movimento de teste
        $stmt = $this->pdo->prepare("INSERT INTO movement (id, name) VALUES (?, ?)");
        $stmt->execute([999, 'Test Movement']);

        // Criar usuários de teste
        $stmt = $this->pdo->prepare("INSERT INTO user (id, name) VALUES (?, ?)");
        $stmt->execute([101, 'Usuario A']);
        $stmt->execute([102, 'Usuario B']);
        $stmt->execute([103, 'Usuario C']);
        $stmt->execute([104, 'Usuario D']);

        // Criar recordes pessoais que produzem empate
        // Usuario A: 190 (posição 1)
        // Usuario B: 180 (posição 2)
        // Usuario C: 180 (posição 2 - empate)
        // Usuario D: 170 (posição 4 - pula a posição 3)
        $stmt = $this->pdo->prepare("
            INSERT INTO personal_record (user_id, movement_id, value, date)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([101, 999, 190.0, '2021-01-01 00:00:00']);
        $stmt->execute([102, 999, 180.0, '2021-01-02 00:00:00']);
        $stmt->execute([103, 999, 180.0, '2021-01-03 00:00:00']);
        $stmt->execute([104, 999, 170.0, '2021-01-04 00:00:00']);
    }

    private function cleanupTestData(): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM personal_record WHERE movement_id = ?');
        $stmt->execute([999]);

        $stmt = $this->pdo->prepare('DELETE FROM user WHERE id IN (?, ?, ?, ?)');
        $stmt->execute([101, 102, 103, 104]);

        $stmt = $this->pdo->prepare('DELETE FROM movement WHERE id = ?');
        $stmt->execute([999]);
    }

    public function testTieBreakingWithRankFunction(): void
    {
        $ranking = $this->repository->getRankingByMovement(999);

        // Deve retornar exatamente 4 resultados
        $this->assertCount(4, $ranking);

        // Validar posições: 1, 2, 2, 4 (não 1, 2, 2, 3)
        $this->assertEquals(1, $ranking[0]['position'], 'Usuario A deve estar na posição 1');
        $this->assertEquals(190.0, $ranking[0]['value'], 'Usuario A deve ter valor 190');

        $this->assertEquals(2, $ranking[1]['position'], 'Usuario B deve estar na posição 2');
        $this->assertEquals(180.0, $ranking[1]['value'], 'Usuario B deve ter valor 180');

        $this->assertEquals(2, $ranking[2]['position'], 'Usuario C deve estar na posição 2 (empate)');
        $this->assertEquals(180.0, $ranking[2]['value'], 'Usuario C deve ter valor 180');

        $this->assertEquals(4, $ranking[3]['position'], 'Usuario D deve estar na posição 4 (pula a posição 3)');
        $this->assertEquals(170.0, $ranking[3]['value'], 'Usuario D deve ter valor 170');
    }

    public function testTieBreakingOrderIsDescending(): void
    {
        $ranking = $this->repository->getRankingByMovement(999);

        // Valores devem estar em ordem decrescente
        $this->assertGreaterThan($ranking[1]['value'], $ranking[0]['value']);
        $this->assertEquals($ranking[1]['value'], $ranking[2]['value']); // Empate
        $this->assertGreaterThan($ranking[3]['value'], $ranking[2]['value']);
    }

    public function testTieBreakingPositionsAreConsecutive(): void
    {
        $ranking = $this->repository->getRankingByMovement(999);

        $positions = array_column($ranking, 'position');

        // Primeira posição deve ser 1
        $this->assertEquals(1, $positions[0]);

        // Última posição deve ser 4 (não 3, pois há um empate)
        $this->assertEquals(4, $positions[count($positions) - 1]);

        // Deve ter exatamente as posições: 1, 2, 2, 4
        $this->assertEquals([1, 2, 2, 4], $positions);
    }

    public function testTieBreakingSameValueSharesPosition(): void
    {
        $ranking = $this->repository->getRankingByMovement(999);

        // Encontrar todas as entradas com valor 180
        $entriesWithValue180 = array_filter($ranking, fn ($entry) => $entry['value'] == 180.0);

        // Deve haver exatamente 2 entradas com valor 180
        $this->assertCount(2, $entriesWithValue180);

        // Todas as entradas com valor 180 devem ter a mesma posição
        $positions = array_unique(array_column($entriesWithValue180, 'position'));
        $this->assertCount(1, $positions, 'Empates devem compartilhar a mesma posição');
        $this->assertEquals(2, reset($positions), 'Posição do empate deve ser 2');
    }
}
