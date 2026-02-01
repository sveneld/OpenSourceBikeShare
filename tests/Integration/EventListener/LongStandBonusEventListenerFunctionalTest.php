<?php

declare(strict_types=1);

namespace BikeShare\Test\Integration\EventListener;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use BikeShare\Enum\CreditChangeType;
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
    private const DEFAULT_LONG_STAND_BONUS = 5.0;

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
            $creditSystem->decreaseCredit($user['userId'], $userCredit, CreditChangeType::BALANCE_ADJUSTMENT);
        }

        $admin = self::getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::ADMIN_PHONE_NUMBER);
        self::getContainer()->get(RentSystemFactory::class)->getRentSystem('web')
            ->returnBike($admin['userId'], self::BIKE_NUMBER, self::STAND1_NAME, '', true);

        $_ENV = $this->originalEnv;
        parent::tearDown();
    }

    /**
     * @dataProvider bonusDataProvider
     */
    public function testLongStandBonusLogic(
        int $longStandDays,
        float $longStandBonus,
        string $pastReturnTime,
        string $returnStandName,
        float $expectedBonus
    ): void {
        $this->configureFeature($longStandDays, $longStandBonus);
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

        // Simulate bike was returned to STAND1 at pastReturnTime
        $db->query(
            'INSERT INTO history (userId, bikeNum, action, parameter, time) 
             VALUES (:userId, :bikeNum, :action, :parameter, :time)',
            [
                'userId' => $admin['userId'],
                'bikeNum' => self::BIKE_NUMBER,
                'action' => Action::RETURN->value,
                'parameter' => $stand1['standId'],
                'time' => $pastReturnTime,
            ]
        );

        // Add credit to allow rent
        $creditSystem->increaseCredit($user['userId'], 100.0, CreditChangeType::CREDIT_ADD);
        $initialCredit = $creditSystem->getUserCredit($user['userId']);

        // Rent bike from STAND1
        $rentSystem->rentBike($user['userId'], self::BIKE_NUMBER);

        // Return bike to returnStandName
        $rentSystem->returnBike($user['userId'], self::BIKE_NUMBER, $returnStandName);

        $finalCredit = $creditSystem->getUserCredit($user['userId']);
        $this->assertSame(
            $initialCredit + $expectedBonus,
            $finalCredit,
            sprintf(
                'Bonus credit expectation failed. Expected bonus: %f. Actual difference: %f',
                $expectedBonus,
                $finalCredit - $initialCredit
            )
        );
    }

    public function bonusDataProvider(): \Generator
    {
        yield 'bonus awarded: long stand, different stand' => [
            'longStandDays' => 7,
            'longStandBonus' => self::DEFAULT_LONG_STAND_BONUS,
            'pastReturnTime' => '2023-09-21 12:00:00', // 10 days ago
            'returnStandName' => self::STAND2_NAME,
            'expectedBonus' => self::DEFAULT_LONG_STAND_BONUS,
        ];

        yield 'no bonus: same stand' => [
            'longStandDays' => 7,
            'longStandBonus' => self::DEFAULT_LONG_STAND_BONUS,
            'pastReturnTime' => '2023-09-21 12:00:00', // 10 days ago
            'returnStandName' => self::STAND1_NAME,
            'expectedBonus' => 0.0,
        ];

        yield 'no bonus: less than threshold' => [
            'longStandDays' => 7,
            'longStandBonus' => self::DEFAULT_LONG_STAND_BONUS,
            'pastReturnTime' => '2023-09-28 12:00:00', // 3 days ago
            'returnStandName' => self::STAND2_NAME,
            'expectedBonus' => 0.0,
        ];

        yield 'no bonus: feature disabled' => [
            'longStandDays' => 0,
            'longStandBonus' => self::DEFAULT_LONG_STAND_BONUS,
            'pastReturnTime' => '2023-09-21 12:00:00', // 10 days ago
            'returnStandName' => self::STAND2_NAME,
            'expectedBonus' => 0.0,
        ];
    }

    private function configureFeature(int $days, float $bonus): void
    {
        $_ENV['CREDIT_SYSTEM_LONG_STAND_DAYS'] = $days;
        $_ENV['CREDIT_SYSTEM_LONG_STAND_BONUS'] = $bonus;
        $_ENV['CREDIT_SYSTEM_ENABLED'] = true;
        $_ENV['WATCHES_NUMBER_TOO_MANY'] = 999; // Disable too many bikes notification
    }
}
