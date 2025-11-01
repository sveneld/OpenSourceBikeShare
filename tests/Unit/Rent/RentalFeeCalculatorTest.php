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
    public function testReturnsNullWhenNoRentHistory(): void
    {
        $rentResult = $this->createMock(DbResultInterface::class);
        $rentResult->method('rowCount')->willReturn(0);

        $db = $this->createMock(DbInterface::class);
        $db->expects($this->once())
            ->method('query')
            ->with(
                'SELECT time FROM ' . 'history' . ' WHERE bikeNum = :bikeNum AND userId = :userId AND action IN (:rentAction, :forceRentAction) ORDER BY time DESC LIMIT 1',
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

        $calculator = new RentalFeeCalculator($db, $clock, $watchesConfig, 2.0, 5.0, 0);

        self::assertNull($calculator->changeCreditEndRental(1, 2));
    }

    public function testCalculatesChargesAndPersistsHistory(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 03:00:00');

        $rentResult = $this->createMock(DbResultInterface::class);
        $rentResult->method('rowCount')->willReturn(1);
        $rentResult->method('fetchAssoc')->willReturn(['time' => '2024-01-01 00:00:00']);

        $returnResult = $this->createMock(DbResultInterface::class);
        $returnResult->method('rowCount')->willReturn(1);
        $returnResult->method('fetchAssoc')->willReturn(['time' => '2023-12-31 23:58:00']);

        $db = $this->createMock(DbInterface::class);
        // Use a callback to inspect the SQL and parameters and return the appropriate mock result
        $mathcer = $this->exactly(2);
        $db->expects($mathcer)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($rentResult, $returnResult, $mathcer, $db) {
                // Use PHPUnit's internal invocation counter to decide which request this is
                switch ($mathcer->getInvocationCount()) {
                    case 1:
                        // first query: latest rent
                        $this->assertStringContainsString('action IN (:rentAction, :forceRentAction)', $sql);
                        $this->assertSame(7, $params['bikeNum']);
                        $this->assertSame(5, $params['userId']);
                        $this->assertSame(Action::RENT->value, $params['rentAction']);
                        $this->assertSame(Action::FORCE_RENT->value, $params['forceRentAction']);

                        return $rentResult;

                    case 2:
                        // second query: latest return
                        $this->assertStringContainsString('action IN (:returnAction, :forceReturnAction)', $sql);
                        $this->assertSame(7, $params['bikeNum']);
                        $this->assertSame(5, $params['userId']);
                        $this->assertSame(Action::RETURN->value, $params['returnAction']);
                        $this->assertSame(Action::FORCE_RETURN->value, $params['forceReturnAction']);

                        return $returnResult;

                    default:
                        $this->fail('Unexpected invocation number: ' . $mathcer->getInvocationCount());
                }
            });

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $watchesConfig = [
            'freetime' => 0,
            'flatpricecycle' => 30,
            'doublepricecycle' => 30,
            'doublepricecyclecap' => 3,
            'longrental' => 2,
        ];

        $calculator = new RentalFeeCalculator($db, $clock, $watchesConfig, 2.0, 5.0, 2);
        $result = $calculator->changeCreditEndRental(7, 5);
        $this->assertSame(47.0, $result['creditChange']);
        $this->assertSame('rerent-2;overfree-2;double-2;double-4;double-8;double-8;double-8;double-8;longrent-5;', $result['changeLog']);
    }
}
