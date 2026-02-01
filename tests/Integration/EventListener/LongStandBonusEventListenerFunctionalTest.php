<?php

declare(strict_types=1);

namespace BikeShare\Test\Integration\EventListener;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use BikeShare\Rent\RentSystemFactory;
use BikeShare\Repository\StandRepository;
use BikeShare\Repository\UserRepository;
use BikeShare\Test\Integration\BikeSharingKernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

class LongStandBonusEventListenerFunctionalTest extends BikeSharingKernelTestCase
{
    use ClockSensitiveTrait;

    private const USER_PHONE_NUMBER = '421951111111';
    private const ADMIN_PHONE_NUMBER = '421951222222';
    private const BIKE_NUMBER = 5;
    private const STAND1_NAME = 'STAND1';
    private const STAND2_NAME = 'STAND2';
    private const LONG_STAND_DAYS = 7;
    private const LONG_STAND_BONUS = 5.0;

    private array $originalEnv = [];

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $user = self::getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $userCredit = $creditSystem->getUserCredit($user['userId']);
        if ($userCredit > 0) {
            $creditSystem->useCredit($user['userId'], $userCredit);
        }

        $admin = self::getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::ADMIN_PHONE_NUMBER);
        self::getContainer()->get(RentSystemFactory::class)->getRentSystem('web')
            ->returnBike($admin['userId'], self::BIKE_NUMBER, self::STAND1_NAME, '', true);

        $_ENV = $this->originalEnv;
        parent::tearDown();
    }

    public function testBonusCreditAwardedWhenReturningLongStandingBikeToDifferentStand(): void
    {
        $this->configureFeature(self::LONG_STAND_DAYS, self::LONG_STAND_BONUS);
        self::ensureKernelShutdown();
        self::bootKernel();
        static::mockTime('2023-10-01 12:00:00');

        $db = self::getContainer()->get(DbInterface::class);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $standRepository = self::getContainer()->get(StandRepository::class);
        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $rentSystem = self::getContainer()->get(RentSystemFactory::class)->getRentSystem('web');

        $user = $userRepository->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $admin = $userRepository->findItemByPhoneNumber(self::ADMIN_PHONE_NUMBER);
        $stand1 = $standRepository->findItemByName(self::STAND1_NAME);

        $db->query('DELETE FROM history WHERE bikeNum = :bikeNum', ['bikeNum' => self::BIKE_NUMBER]);

        // Simulate bike was returned to STAND1 10 days ago
        $db->query(
            'INSERT INTO history (userId, bikeNum, action, parameter, time) 
             VALUES (:userId, :bikeNum, :action, :parameter, :time)',
            [
                'userId' => $admin['userId'],
                'bikeNum' => self::BIKE_NUMBER,
                'action' => Action::RETURN->value,
                'parameter' => $stand1['standId'],
                'time' => '2023-09-21 12:00:00',
            ]
        );

        // Add credit to allow rent
        $creditSystem->addCredit($user['userId'], 100.0);
        $initialCredit = $creditSystem->getUserCredit($user['userId']);

        // Rent bike from STAND1
        $rentSystem->rentBike($user['userId'], self::BIKE_NUMBER);

        // Return bike to STAND2 (different stand)
        $rentSystem->returnBike($user['userId'], self::BIKE_NUMBER, self::STAND2_NAME);

        $finalCredit = $creditSystem->getUserCredit($user['userId']);
        $this->assertSame(
            $initialCredit + self::LONG_STAND_BONUS,
            $finalCredit,
            'Bonus credit should be awarded when returning long-standing bike to different stand'
        );
    }

    public function testNoBonusWhenReturningToSameStand(): void
    {
        $this->configureFeature(self::LONG_STAND_DAYS, self::LONG_STAND_BONUS);
        self::ensureKernelShutdown();
        self::bootKernel();
        static::mockTime('2023-10-01 12:00:00');

        $db = self::getContainer()->get(DbInterface::class);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $standRepository = self::getContainer()->get(StandRepository::class);
        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $rentSystem = self::getContainer()->get(RentSystemFactory::class)->getRentSystem('web');

        $user = $userRepository->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $admin = $userRepository->findItemByPhoneNumber(self::ADMIN_PHONE_NUMBER);
        $stand1 = $standRepository->findItemByName(self::STAND1_NAME);

        $db->query('DELETE FROM history WHERE bikeNum = :bikeNum', ['bikeNum' => self::BIKE_NUMBER]);

        // Simulate bike was returned to STAND1 10 days ago
        $db->query(
            'INSERT INTO history (userId, bikeNum, action, parameter, time)
             VALUES (:userId, :bikeNum, :action, :parameter, :time)',
            [
                'userId' => $admin['userId'],
                'bikeNum' => self::BIKE_NUMBER,
                'action' => Action::RETURN->value,
                'parameter' => $stand1['standId'],
                'time' => '2023-09-21 12:00:00',
            ]
        );

        // Add credit to allow rent
        $creditSystem->addCredit($user['userId'], 100.0);
        $initialCredit = $creditSystem->getUserCredit($user['userId']);

        // Rent bike from STAND1
        $rentSystem->rentBike($user['userId'], self::BIKE_NUMBER);

        // Return bike to STAND1 (same stand)
        $rentSystem->returnBike($user['userId'], self::BIKE_NUMBER, self::STAND1_NAME);

        $finalCredit = $creditSystem->getUserCredit($user['userId']);
        $this->assertSame(
            $initialCredit,
            $finalCredit,
            'No bonus credit should be awarded when returning to the same stand'
        );
    }

    public function testNoBonusWhenBikeStoodLessThanThreshold(): void
    {
        $this->configureFeature(self::LONG_STAND_DAYS, self::LONG_STAND_BONUS);
        self::ensureKernelShutdown();
        self::bootKernel();
        static::mockTime('2023-10-01 12:00:00');

        $db = self::getContainer()->get(DbInterface::class);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $standRepository = self::getContainer()->get(StandRepository::class);
        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $rentSystem = self::getContainer()->get(RentSystemFactory::class)->getRentSystem('web');

        $user = $userRepository->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $admin = $userRepository->findItemByPhoneNumber(self::ADMIN_PHONE_NUMBER);
        $stand1 = $standRepository->findItemByName(self::STAND1_NAME);

        $db->query('DELETE FROM history WHERE bikeNum = :bikeNum', ['bikeNum' => self::BIKE_NUMBER]);

        // Simulate bike was returned to STAND1 only 3 days ago (less than 7 days threshold)
        $db->query(
            'INSERT INTO history (userId, bikeNum, action, parameter, time) 
             VALUES (:userId, :bikeNum, :action, :parameter, :time)',
            [
                'userId' => $admin['userId'],
                'bikeNum' => self::BIKE_NUMBER,
                'action' => Action::RETURN->value,
                'parameter' => $stand1['standId'],
                'time' => '2023-09-28 12:00:00',
            ]
        );

        // Add credit to allow rent
        $creditSystem->addCredit($user['userId'], 100.0);
        $initialCredit = $creditSystem->getUserCredit($user['userId']);

        // Rent bike from STAND1
        $rentSystem->rentBike($user['userId'], self::BIKE_NUMBER);

        // Return bike to STAND2 (different stand, but bike hasn't stood long enough)
        $rentSystem->returnBike($user['userId'], self::BIKE_NUMBER, self::STAND2_NAME);

        $finalCredit = $creditSystem->getUserCredit($user['userId']);
        $this->assertSame(
            $initialCredit,
            $finalCredit,
            'No bonus credit should be awarded when bike stood less than threshold days'
        );
    }

    public function testNoBonusWhenFeatureDisabled(): void
    {
        $this->configureFeature(0, self::LONG_STAND_BONUS); // 0 days = disabled
        self::ensureKernelShutdown();
        self::bootKernel();
        static::mockTime('2023-10-01 12:00:00');

        $db = self::getContainer()->get(DbInterface::class);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $standRepository = self::getContainer()->get(StandRepository::class);
        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $rentSystem = self::getContainer()->get(RentSystemFactory::class)->getRentSystem('web');

        $user = $userRepository->findItemByPhoneNumber(self::USER_PHONE_NUMBER);
        $admin = $userRepository->findItemByPhoneNumber(self::ADMIN_PHONE_NUMBER);
        $stand1 = $standRepository->findItemByName(self::STAND1_NAME);

        $db->query('DELETE FROM history WHERE bikeNum = :bikeNum', ['bikeNum' => self::BIKE_NUMBER]);

        // Simulate bike was returned to STAND1 10 days ago
        $db->query(
            'INSERT INTO history (userId, bikeNum, action, parameter, time) 
             VALUES (:userId, :bikeNum, :action, :parameter, :time)',
            [
                'userId' => $admin['userId'],
                'bikeNum' => self::BIKE_NUMBER,
                'action' => Action::RETURN->value,
                'parameter' => $stand1['standId'],
                'time' => '2023-09-21 12:00:00',
            ]
        );

        // Add credit to allow rent
        $creditSystem->addCredit($user['userId'], 100.0);
        $initialCredit = $creditSystem->getUserCredit($user['userId']);

        // Rent bike from STAND1
        $rentSystem->rentBike($user['userId'], self::BIKE_NUMBER);

        // Return bike to STAND2
        $rentSystem->returnBike($user['userId'], self::BIKE_NUMBER, self::STAND2_NAME);

        $finalCredit = $creditSystem->getUserCredit($user['userId']);
        $this->assertSame(
            $initialCredit,
            $finalCredit,
            'No bonus credit should be awarded when feature is disabled'
        );
    }

    private function configureFeature(int $days, float $bonus): void
    {
        $_ENV['CREDIT_SYSTEM_LONG_STAND_DAYS'] = $days;
        $_ENV['CREDIT_SYSTEM_LONG_STAND_BONUS'] = $bonus;
        $_ENV['CREDIT_SYSTEM_ENABLED'] = true;
        $_ENV['WATCHES_NUMBER_TOO_MANY'] = 999; // Disable too many bikes notification
    }
}
