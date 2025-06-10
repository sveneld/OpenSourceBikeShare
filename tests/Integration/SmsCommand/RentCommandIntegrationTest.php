<?php

declare(strict_types=1);

namespace Test\BikeShare\Integration\SmsCommand;

use BikeShare\App\Entity\User;
use BikeShare\Rent\RentSystemInterface;
use BikeShare\SmsCommand\CommandExecutor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RentCommandIntegrationTest extends KernelTestCase
{
    private CommandExecutor $executor;
    private DummyRentSystem $rentSystem;

    protected function setUp(): void
    {
        $_ENV['SMS_CONNECTOR'] = 'disabled';
        self::bootKernel();
        $container = static::getContainer();
        $this->rentSystem = new DummyRentSystem();
        $container->set(RentSystemInterface::class, $this->rentSystem);
        $this->executor = $container->get(CommandExecutor::class);
    }

    public function testRentDelegatesToRentSystem(): void
    {
        $user = new User(1, '123456', 'u@example.com', 'pw', 'city', 'name', 0, true);
        $response = $this->executor->execute('RENT 42', $user);
        $this->assertSame('rent 42 for user 1', $response);
        $this->assertSame(1, $this->rentSystem->lastUserId);
        $this->assertSame(42, $this->rentSystem->lastBikeId);
    }
}

class DummyRentSystem implements RentSystemInterface
{
    public int $lastUserId = 0;
    public int $lastBikeId = 0;

    public function rentBike($userId, $bikeId, $force = false)
    {
        $this->lastUserId = $userId;
        $this->lastBikeId = $bikeId;
        return "rent $bikeId for user $userId";
    }

    public function returnBike($userId, $bikeId, $standName, $note = '', $force = false)
    {
    }

    public function revertBike($userId, $bikeId)
    {
    }

    public static function getType(): string
    {
        return 'dummy';
    }
}
