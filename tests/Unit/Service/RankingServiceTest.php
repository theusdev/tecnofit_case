<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\MovementRanking;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\MovementRepository;
use App\Repository\PersonalRecordRepository;
use App\Service\RankingService;
use PHPUnit\Framework\TestCase;

class RankingServiceTest extends TestCase
{
    private MovementRepository $movementRepository;
    private PersonalRecordRepository $personalRecordRepository;
    private RankingService $service;

    protected function setUp(): void
    {
        $this->movementRepository = $this->createMock(MovementRepository::class);
        $this->personalRecordRepository = $this->createMock(PersonalRecordRepository::class);
        $this->service = new RankingService($this->movementRepository, $this->personalRecordRepository);
    }

    public function testGetRankingByIdReturnsMovementRanking(): void
    {
        $movementId = 1;
        $movementData = ['id' => 1, 'name' => 'Deadlift'];
        $rankingData = [
            ['position' => 1, 'user_id' => 1, 'user_name' => 'Joao', 'value' => 180.0, 'date' => '2021-01-02T00:00:00Z'],
        ];

        $this->movementRepository
            ->expects($this->once())
            ->method('findById')
            ->with($movementId)
            ->willReturn($movementData);

        $this->personalRecordRepository
            ->expects($this->once())
            ->method('getRankingByMovement')
            ->with($movementId, null)
            ->willReturn($rankingData);

        $this->personalRecordRepository
            ->expects($this->once())
            ->method('countUsersByMovement')
            ->with($movementId)
            ->willReturn(1);

        $ranking = $this->service->getRanking($movementId);

        $this->assertInstanceOf(MovementRanking::class, $ranking);
        $this->assertEquals(1, $ranking->movementId);
        $this->assertEquals('Deadlift', $ranking->movementName);
        $this->assertCount(1, $ranking->entries);
        $this->assertEquals(1, $ranking->totalUsers);
    }

    public function testGetRankingByNameReturnsMovementRanking(): void
    {
        $movementName = 'Deadlift';
        $movementData = ['id' => 1, 'name' => 'Deadlift'];
        $rankingData = [
            ['position' => 1, 'user_id' => 1, 'user_name' => 'Joao', 'value' => 180.0, 'date' => '2021-01-02T00:00:00Z'],
        ];

        $this->movementRepository
            ->expects($this->once())
            ->method('findByName')
            ->with($movementName)
            ->willReturn($movementData);

        $this->personalRecordRepository
            ->expects($this->once())
            ->method('getRankingByMovement')
            ->with(1, null)
            ->willReturn($rankingData);

        $this->personalRecordRepository
            ->expects($this->once())
            ->method('countUsersByMovement')
            ->with(1)
            ->willReturn(1);

        $ranking = $this->service->getRanking($movementName);

        $this->assertInstanceOf(MovementRanking::class, $ranking);
        $this->assertEquals(1, $ranking->movementId);
        $this->assertEquals('Deadlift', $ranking->movementName);
    }

    public function testGetRankingThrowsNotFoundExceptionWhenMovementNotFoundById(): void
    {
        $this->movementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Movimento com ID 999 não encontrado');

        $this->service->getRanking(999);
    }

    public function testGetRankingThrowsNotFoundExceptionWhenMovementNotFoundByName(): void
    {
        $this->movementRepository
            ->expects($this->once())
            ->method('findByName')
            ->with('Inexistente')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Movimento com nome 'Inexistente' não encontrado");

        $this->service->getRanking('Inexistente');
    }

    public function testGetRankingThrowsValidationExceptionForInvalidId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('O ID do movimento deve ser um número positivo');

        $this->service->getRanking(0);
    }

    public function testGetRankingThrowsValidationExceptionForInvalidLimit(): void
    {
        $movementData = ['id' => 1, 'name' => 'Deadlift'];

        $this->movementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($movementData);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('O limite deve ser um número positivo');

        $this->service->getRanking(1, 0);
    }

    public function testGetRankingThrowsValidationExceptionForEmptyName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('O nome do movimento não pode ser vazio');

        $this->service->getRanking('');
    }

    public function testGetRankingReturnsEmptyRankingWhenNoRecords(): void
    {
        $movementData = ['id' => 1, 'name' => 'Deadlift'];

        $this->movementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($movementData);

        $this->personalRecordRepository
            ->expects($this->once())
            ->method('getRankingByMovement')
            ->with(1, null)
            ->willReturn([]);

        $this->personalRecordRepository
            ->expects($this->once())
            ->method('countUsersByMovement')
            ->with(1)
            ->willReturn(0);

        $ranking = $this->service->getRanking(1);

        $this->assertInstanceOf(MovementRanking::class, $ranking);
        $this->assertEmpty($ranking->entries);
        $this->assertEquals(0, $ranking->totalUsers);
    }

    public function testListMovementsReturnsArray(): void
    {
        $movements = [
            ['id' => 1, 'name' => 'Deadlift'],
            ['id' => 2, 'name' => 'Back Squat'],
        ];

        $this->movementRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($movements);

        $result = $this->service->listMovements();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
