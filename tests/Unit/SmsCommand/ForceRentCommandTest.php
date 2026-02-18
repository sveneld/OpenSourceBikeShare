<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\SmsCommand;

use BikeShare\App\Entity\User;
use BikeShare\Rent\DTO\RentSystemResult;
use BikeShare\Rent\Enum\RentSystemType;
use BikeShare\Rent\RentSystemInterface;
use BikeShare\SmsCommand\ForceRentCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForceRentCommandTest extends TestCase
{
    private TranslatorInterface&MockObject $translatorMock;
    private RentSystemInterface&MockObject $rentSystemMock;
    private ForceRentCommand $command;

    protected function setUp(): void
    {
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->rentSystemMock = $this->createMock(RentSystemInterface::class);
        $this->command = new ForceRentCommand($this->translatorMock, $this->rentSystemMock);
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
        $expectedMessage = 'Rent successful';

        $this->translatorMock->expects($this->never())->method('trans');
        $userMock->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->rentSystemMock
            ->expects($this->once())
            ->method('rentBike')
            ->with($userId, $bikeNumber, true)
            ->willReturn(new RentSystemResult(false, $expectedMessage, 'bike.rent.success', [], RentSystemType::SMS));

        $this->assertSame($expectedMessage, ($this->command)($userMock, $bikeNumber));
    }

    public function testGetHelpMessage(): void
    {
        $expectedMessage = 'Translated help message';
        $this->rentSystemMock->expects($this->never())->method('rentBike');
        $this->translatorMock
            ->expects($this->once())
            ->method('trans')
            ->with('with bike number: {example}', ['example' => 'FORCERENT 42'])
            ->willReturn($expectedMessage);

        $this->assertSame($expectedMessage, $this->command->getHelpMessage());
    }
}
