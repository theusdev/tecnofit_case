<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Database\Connection;
use App\Repository\PersonalRecordRepository;
use PHPUnit\Framework\TestCase;

class PersonalRecordRepositoryTest extends TestCase
{
    private PersonalRecordRepository $repository;

    protected function setUp(): void
    {
        Connection::resetInstance();
        $this->repository = new PersonalRecordRepository(Connection::getInstance());
    }

    protected function tearDown(): void
    {
        Connection::resetInstance();
    }

    public function testGetRankingByMovementReturnsCorrectOrder(): void
    {
        $ranking = $this->repository->getRankingByMovement(1);

        $this->assertNotEmpty($ranking);
        $this->assertArrayHasKey('position', $ranking[0]);
        $this->assertArrayHasKey('user_id', $ranking[0]);
        $this->assertArrayHasKey('user_name', $ranking[0]);
        $this->assertArrayHasKey('value', $ranking[0]);
        $this->assertArrayHasKey('date', $ranking[0]);

        // Verificar que a posição está em ordem crescente
        $positions = array_column($ranking, 'position');
        $this->assertEquals($positions, array_values(array_unique($positions)));
    }

    public function testGetRankingByMovementWithLimit(): void
    {
        $ranking = $this->repository->getRankingByMovement(1, 2);

        $this->assertCount(2, $ranking);
    }

    public function testCountUsersByMovement(): void
    {
        $count = $this->repository->countUsersByMovement(1);

        $this->assertGreaterThan(0, $count);
        $this->assertIsInt($count);
    }

    public function testGetRankingByMovementReturnsEmptyForNonExistentMovement(): void
    {
        $ranking = $this->repository->getRankingByMovement(999);

        $this->assertEmpty($ranking);
    }
}
