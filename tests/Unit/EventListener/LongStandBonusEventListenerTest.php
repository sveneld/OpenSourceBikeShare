<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\EventListener;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Event\BikeReturnEvent;
use BikeShare\EventListener\LongStandBonusEventListener;
use BikeShare\Repository\HistoryRepository;
use BikeShare\Repository\StandRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\ClockInterface;

class LongStandBonusEventListenerTest extends TestCase
{
    private const BIKE_NUMBER = 1;
    private const USER_ID = 1;
    private const STAND_NAME = 'STAND2';
    private const PREVIOUS_STAND_ID = 1;
    private const CURRENT_STAND_ID = 2;
    private const LONG_STAND_DAYS = 7;
    private const LONG_STAND_BONUS = 5.0;
    private const CURRENT_TIME = '2023-10-15 12:00:00';

    private ClockInterface&MockObject $clock;
    private HistoryRepository&MockObject $historyRepository;
    private StandRepository&MockObject $standRepository;
    private CreditSystemInterface&MockObject $creditSystem;

    protected function setUp(): void
    {
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable(self::CURRENT_TIME));

        $this->historyRepository = $this->createMock(HistoryRepository::class);
        $this->standRepository = $this->createMock(StandRepository::class);
        $this->creditSystem = $this->createMock(CreditSystemInterface::class);
    }

    public function testBonusNotAwardedWhenFeatureDisabled(): void
    {
        $listener = $this->createListener(longStandDays: 0);

        $this->historyRepository->expects($this->never())->method('findPreviousBikeReturn');
        $this->creditSystem->expects($this->never())->method('increaseCredit');

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusNotAwardedWhenNoReturnHistory(): void
    {
        $listener = $this->createListener();

        $this->historyRepository
            ->expects($this->once())
            ->method('findPreviousBikeReturn')
            ->with(self::BIKE_NUMBER)
            ->willReturn(null);

        $this->creditSystem->expects($this->never())->method('increaseCredit');

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusNotAwardedWhenStandNotFound(): void
    {
        $listener = $this->createListener();

        $this->historyRepository
            ->expects($this->once())
            ->method('findPreviousBikeReturn')
            ->willReturn(['standId' => self::PREVIOUS_STAND_ID, 'time' => '2023-10-01 12:00:00']);

        $this->standRepository
            ->expects($this->once())
            ->method('findItemByName')
            ->with(self::STAND_NAME)
            ->willReturn(null);

        $this->creditSystem->expects($this->never())->method('increaseCredit');

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusNotAwardedWhenReturnedToSameStand(): void
    {
        $listener = $this->createListener();

        $this->historyRepository
            ->expects($this->once())
            ->method('findPreviousBikeReturn')
            ->willReturn(['standId' => self::CURRENT_STAND_ID, 'time' => '2023-10-01 12:00:00']);

        $this->standRepository
            ->expects($this->once())
            ->method('findItemByName')
            ->with(self::STAND_NAME)
            ->willReturn(['standId' => self::CURRENT_STAND_ID]);

        $this->creditSystem->expects($this->never())->method('increaseCredit');

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusNotAwardedWhenBikeStoodLessThanThreshold(): void
    {
        $listener = $this->createListener();

        // Bike was returned 5 days ago (less than 7 days threshold)
        $this->historyRepository
            ->expects($this->once())
            ->method('findPreviousBikeReturn')
            ->willReturn(['standId' => self::PREVIOUS_STAND_ID, 'time' => '2023-10-10 12:00:00']);

        $this->standRepository
            ->expects($this->once())
            ->method('findItemByName')
            ->with(self::STAND_NAME)
            ->willReturn(['standId' => self::CURRENT_STAND_ID]);

        $this->creditSystem->expects($this->never())->method('increaseCredit');

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusAwardedWhenAllConditionsMet(): void
    {
        $listener = $this->createListener();

        // Bike was returned 14 days ago (more than 7 days threshold)
        $this->historyRepository
            ->expects($this->once())
            ->method('findPreviousBikeReturn')
            ->willReturn(['standId' => self::PREVIOUS_STAND_ID, 'time' => '2023-10-01 12:00:00']);

        $this->standRepository
            ->expects($this->once())
            ->method('findItemByName')
            ->with(self::STAND_NAME)
            ->willReturn(['standId' => self::CURRENT_STAND_ID]);

        $this->creditSystem
            ->expects($this->once())
            ->method('increaseCredit')
            ->with(self::USER_ID, self::LONG_STAND_BONUS, $this->anything());

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusAwardedWhenExactlyAtThreshold(): void
    {
        $listener = $this->createListener();

        // Bike was returned exactly 7 days ago
        $this->historyRepository
            ->expects($this->once())
            ->method('findPreviousBikeReturn')
            ->willReturn(['standId' => self::PREVIOUS_STAND_ID, 'time' => '2023-10-08 12:00:00']);

        $this->standRepository
            ->expects($this->once())
            ->method('findItemByName')
            ->with(self::STAND_NAME)
            ->willReturn(['standId' => self::CURRENT_STAND_ID]);

        $this->creditSystem
            ->expects($this->once())
            ->method('increaseCredit')
            ->with(self::USER_ID, self::LONG_STAND_BONUS, $this->anything());

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, false));
    }

    public function testBonusNotAwardedOnForceReturn(): void
    {
        $listener = $this->createListener();

        $this->historyRepository->expects($this->never())->method('findPreviousBikeReturn');
        $this->standRepository->expects($this->never())->method('findItemByName');
        $this->creditSystem->expects($this->never())->method('increaseCredit');

        $listener(new BikeReturnEvent(self::BIKE_NUMBER, self::STAND_NAME, self::USER_ID, true));
    }

    private function createListener(
        int $longStandDays = self::LONG_STAND_DAYS,
        float $longStandBonus = self::LONG_STAND_BONUS,
    ): LongStandBonusEventListener {
        return new LongStandBonusEventListener(
            $longStandDays,
            $longStandBonus,
            $this->historyRepository,
            $this->standRepository,
            $this->creditSystem,
            $this->clock,
        );
    }
}
