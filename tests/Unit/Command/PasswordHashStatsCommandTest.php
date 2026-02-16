<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\Command;

use BikeShare\Command\PasswordHashStatsCommand;
use BikeShare\Db\DbInterface;
use BikeShare\Db\DbResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PasswordHashStatsCommandTest extends TestCase
{
    private DbInterface&MockObject $db;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbInterface::class);

        $command = new PasswordHashStatsCommand($this->db);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteReturnsFailureWhenStatsQueryFails(): void
    {
        $dbResult = $this->createMock(DbResultInterface::class);
        $this->db->expects($this->once())->method('query')->willReturn($dbResult);
        $dbResult->expects($this->once())->method('fetchAssoc')->willReturn(false);

        $this->commandTester->execute([]);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Unable to load password hash statistics.', $output);
    }

    public function testExecuteRethrowsDatabaseException(): void
    {
        $this->db
            ->expects($this->once())
            ->method('query')
            ->willThrowException(new \RuntimeException('SQLSTATE[HY000]: sensitive detail'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: sensitive detail');
        $this->commandTester->execute([]);
    }

    public function testExecuteReturnsSuccessWithWarningForEmptyStats(): void
    {
        $dbResult = $this->createMock(DbResultInterface::class);
        $this->db->expects($this->once())->method('query')->willReturn($dbResult);
        $dbResult->expects($this->once())->method('fetchAssoc')->willReturn([]);

        $this->commandTester->execute([]);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('No users found in the database.', $output);
    }
}
