<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\Rent;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Db\DbInterface;
use BikeShare\Db\DbResultInterface;
use BikeShare\Enum\Action;
use BikeShare\Rent\RentalFeeCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\ClockInterface;

class RentalFeeCalculatorTest extends TestCase
{
    public function testReturnsNullWhenCreditSystemDisabled(): void
    {
        $creditSystem = $this->createMock(CreditSystemInterface::class);
        $creditSystem->expects($this->once())->method('isEnabled')->willReturn(false);
        $db = $this->createMock(DbInterface::class);
        $db->expects($this->never())->method('query');
        $clock = $this->createMock(ClockInterface::class);
        $watchesConfig = [];

        $calculator = new RentalFeeCalculator($creditSystem, $db, $clock, $watchesConfig);

        self::assertNull($calculator->changeCreditEndRental(1, 2));
    }

    public function testReturnsNullWhenNoRentHistory(): void
    {
        $creditSystem = $this->createMock(CreditSystemInterface::class);
        $creditSystem->expects($this->once())->method('isEnabled')->willReturn(true);
        $creditSystem->expects($this->once())->method('getUserCredit')->with(2)->willReturn(10.0);

        $rentResult = $this->createMock(DbResultInterface::class);
        $rentResult->method('rowCount')->willReturn(0);

        $db = $this->createMock(DbInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->with(
                'SELECT time FROM history WHERE bikeNum = :bikeNum AND userId = :userId AND action IN (:rentAction, :forceRentAction) ORDER BY time DESC LIMIT 1',
                [
                    'bikeNum' => 1,
                    'userId' => 2,
                    'rentAction' => Action::RENT->value,
                    'forceRentAction' => Action::FORCE_RENT->value,
                ]
            )
            ->willReturn($rentResult);

        $clock = $this->createMock(ClockInterface::class);

        $watchesConfig = [
            'freetime' => 15,
        ];

        $calculator = new RentalFeeCalculator($creditSystem, $db, $clock, $watchesConfig);

        self::assertNull($calculator->changeCreditEndRental(1, 2));
    }

    public function testCalculatesChargesAndPersistsHistory(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 03:00:00');

        $creditSystem = $this->createMock(CreditSystemInterface::class);
        $creditSystem->expects($this->once())->method('isEnabled')->willReturn(true);
        $creditSystem->expects($this->once())->method('getUserCredit')->with(5)->willReturn(100.0);
        $creditSystem->method('getRentalFee')->willReturn(2.0);
        $creditSystem->method('getPriceCycle')->willReturn(2);
        $creditSystem->method('getLongRentalFee')->willReturn(5.0);
        $creditSystem->expects($this->once())->method('useCredit')->with(5, 47.0);

        $rentResult = $this->createMock(DbResultInterface::class);
        $rentResult->method('rowCount')->willReturn(1);
        $rentResult->method('fetchAssoc')->willReturn(['time' => '2024-01-01 00:00:00']);

        $returnResult = $this->createMock(DbResultInterface::class);
        $returnResult->method('rowCount')->willReturn(1);
        $returnResult->method('fetchAssoc')->willReturn(['time' => '2023-12-31 23:58:00']);

        $db = $this->createMock(DbInterface::class);
        $db->expects($this->exactly(4))
            ->method('query')
            ->withConsecutive(
                [
                    'SELECT time FROM history WHERE bikeNum = :bikeNum AND userId = :userId AND action IN (:rentAction, :forceRentAction) ORDER BY time DESC LIMIT 1',
                    [
                        'bikeNum' => 7,
                        'userId' => 5,
                        'rentAction' => Action::RENT->value,
                        'forceRentAction' => Action::FORCE_RENT->value,
                    ],
                ],
                [
                    'SELECT time FROM history WHERE bikeNum = :bikeNum AND userId = :userId AND action IN (:returnAction, :forceReturnAction) ORDER BY time DESC LIMIT 1',
                    [
                        'bikeNum' => 7,
                        'userId' => 5,
                        'returnAction' => Action::RETURN->value,
                        'forceReturnAction' => Action::FORCE_RETURN->value,
                    ],
                ],
                [
                    'INSERT INTO history SET userId = :userId, bikeNum = :bikeNum, action = :action, parameter = :creditChange, time = :time',
                    $this->callback(function (array $params): bool {
                        $this->assertSame(5, $params['userId']);
                        $this->assertSame(7, $params['bikeNum']);
                        $this->assertSame(Action::CREDIT_CHANGE->value, $params['action']);
                        $this->assertSame('47|rerent-2;overfree-2;double-2;double-4;double-8;double-8;double-8;double-8;longrent-5;', $params['creditChange']);
                        $this->assertSame('2024-01-01 03:00:00', $params['time']);

                        return true;
                    }),
                ],
                [
                    'INSERT INTO history SET userId = :userId, bikeNum = :bikeNum, action = :action, parameter = :userCredit, time = :time',
                    $this->callback(function (array $params): bool {
                        $this->assertSame(5, $params['userId']);
                        $this->assertSame(7, $params['bikeNum']);
                        $this->assertSame(Action::CREDIT->value, $params['action']);
                        $this->assertSame(53.0, $params['userCredit']);
                        $this->assertSame('2024-01-01 03:00:00', $params['time']);

                        return true;
                    }),
                ],
            )
            ->willReturnOnConsecutiveCalls(
                $rentResult,
                $returnResult,
                $this->createMock(DbResultInterface::class),
                $this->createMock(DbResultInterface::class),
            );

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $watchesConfig = [
            'freetime' => 0,
            'flatpricecycle' => 30,
            'doublepricecycle' => 30,
            'doublepricecyclecap' => 3,
            'longrental' => 2,
        ];

        $calculator = new RentalFeeCalculator($creditSystem, $db, $clock, $watchesConfig);;

        self::assertSame(47.0, $calculator->changeCreditEndRental(7, 5));
        $this->assertSame(47.0, $calculator->changeCreditEndRental(7, 5));
    }
}
