<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\SmsCommand;

use BikeShare\App\Entity\User;
use BikeShare\Rent\DTO\RentSystemResult;
use BikeShare\Rent\Enum\RentSystemType;
use BikeShare\Rent\RentSystemInterface;
use BikeShare\SmsCommand\RevertCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RevertCommandTest extends TestCase
{
    private TranslatorInterface&MockObject $translatorMock;
    private RentSystemInterface&MockObject $rentSystemMock;
    private RevertCommand $command;

    protected function setUp(): void
    {
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->rentSystemMock = $this->createMock(RentSystemInterface::class);
        $this->command = new RevertCommand($this->translatorMock, $this->rentSystemMock);
    }

    protected function tearDown(): void
    {
        unset($this->translatorMock, $this->rentSystemMock, $this->command);
    }

    public function testInvoke(): void
    {
        $userMock = $this->createMock(User::class);
        $userId = 123;
        $bikeNumber = 456;
        $expectedMessage = 'Bike 42 reverted.';

        $this->translatorMock->expects($this->never())->method('trans');
        $userMock->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->rentSystemMock
            ->expects($this->once())
            ->method('revertBike')
            ->with($userId, $bikeNumber)
            ->willReturn(new RentSystemResult(false, $expectedMessage, 'bike.revert.success', [], RentSystemType::SMS));

        $this->assertSame($expectedMessage, ($this->command)($userMock, $bikeNumber));
    }

    public function testGetHelpMessage(): void
    {
        $expectedMessage = 'Bike rented successfully.';
        $this->rentSystemMock->expects($this->never())->method('revertBike');
        $this->translatorMock
            ->expects($this->once())
            ->method('trans')
            ->with('with bike number: {example}', ['example' => 'REVERT 42'])
            ->willReturn($expectedMessage);

        $this->assertSame($expectedMessage, $this->command->getHelpMessage());
    }
}
