<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Database\Connection;
use App\Domain\MovementRanking;
use App\Domain\RankingEntry;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\MovementRepository;
use App\Repository\PersonalRecordRepository;
use App\Service\RankingService;
use PHPUnit\Framework\TestCase;

class RankingServiceTest extends TestCase
{
    private RankingService $service;

    protected function setUp(): void
    {
        Connection::resetInstance();
        $pdo = Connection::getInstance();
        $movementRepository = new MovementRepository($pdo);
        $personalRecordRepository = new PersonalRecordRepository($pdo);
        $this->service = new RankingService($movementRepository, $personalRecordRepository);
    }

    protected function tearDown(): void
    {
        Connection::resetInstance();
    }

    public function testListMovementsReturnsArray(): void
    {
        $movements = $this->service->listMovements();

        $this->assertIsArray($movements);
        $this->assertNotEmpty($movements);
        $this->assertArrayHasKey('id', $movements[0]);
        $this->assertArrayHasKey('name', $movements[0]);
    }

    public function testGetRankingByMovementReturnsMovementRanking(): void
    {
        $ranking = $this->service->getRankingByMovement(1);

        $this->assertInstanceOf(MovementRanking::class, $ranking);
        $this->assertEquals(1, $ranking->movementId);
        $this->assertIsString($ranking->movementName);
        $this->assertIsArray($ranking->entries);
        $this->assertGreaterThan(0, $ranking->totalUsers);

        if (!empty($ranking->entries)) {
            $this->assertInstanceOf(RankingEntry::class, $ranking->entries[0]);
        }
    }

    public function testGetRankingByMovementWithLimit(): void
    {
        $ranking = $this->service->getRankingByMovement(1, 2);

        $this->assertCount(2, $ranking->entries);
    }

    public function testGetRankingByMovementThrowsValidationExceptionForInvalidId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('O ID do movimento deve ser um número positivo');

        $this->service->getRankingByMovement(0);
    }

    public function testGetRankingByMovementThrowsValidationExceptionForInvalidLimit(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('O limite deve ser um número positivo');

        $this->service->getRankingByMovement(1, 0);
    }

    public function testGetRankingByMovementThrowsNotFoundExceptionForNonExistentMovement(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Movimento com ID 999 não encontrado');

        $this->service->getRankingByMovement(999);
    }
}
