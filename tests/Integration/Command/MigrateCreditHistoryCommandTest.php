<?php

declare(strict_types=1);

namespace BikeShare\Test\Integration\Command;

use BikeShare\Enum\Action;
use BikeShare\Enum\CreditChangeType;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCreditHistoryCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:migrate_credit_history');
        $this->commandTester = new CommandTester($command);

        $this->db = self::getContainer()->get('BikeShare\Db\DbInterface');

        // Clean up history and credit tables for testing
        $this->db->query("DELETE FROM history");
        $this->db->query("DELETE FROM credit");
    }

    public function testExecuteDryRun(): void
    {
        // Setup initial state: User with 100 credit and some history
        $userId = 1;
        $this->db->query(
            "INSERT INTO credit (userId, credit) VALUES (:userId, :credit)",
            ['userId' => $userId, 'credit' => 100.0]
        );

        // Add legacy history record
        $this->db->query(
            "INSERT INTO history (userId, bikeNum, action, parameter, time) 
            VALUES (:userId, 0, :action, :parameter, :time)",
            [
                'userId' => $userId,
                'action' => Action::CREDIT_CHANGE->value,
                'parameter' => '50.0|flat-50',
                'time' => '2023-01-01 10:00:00'
            ]
        );

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Dry run complete', $output);
        $this->assertStringContainsString('Users processed', $output);

        // Verify no changes were made
        $result = $this->db->query("SELECT parameter FROM history WHERE userId = :userId", ['userId' => $userId]);
        $record = $result->fetchAssoc();
        $this->assertEquals('50.0|flat-50', $record['parameter']);
    }

    public function testExecuteMigrationWithAdjustment(): void
    {
        // Setup initial state: User with 100 credit but history only sums to 50
        // This should trigger a balance adjustment of +50
        $userId = 2;
        $this->db->query(
            "INSERT INTO credit (userId, credit) VALUES (:userId, :credit)",
            ['userId' => $userId, 'credit' => 100.0]
        );

        $this->db->query(
            "INSERT INTO history (userId, bikeNum, action, parameter, time)
            VALUES (:userId, 0, :action, :parameter, :time)",
            [
                'userId' => $userId,
                'action' => Action::CREDIT_CHANGE->value,
                'parameter' => '50.0|flat-50',
                'time' => '2023-01-01 10:00:00'
            ]
        );

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Migration completed successfully', $output);

        // Verify migration
        $result = $this->db->query(
            "SELECT parameter, time FROM history WHERE userId = :userId ORDER BY time ASC",
            ['userId' => $userId]
        );
        $records = $result->fetchAllAssoc();

        // Expect 2 records: 1 adjustment + 1 migrated
        $this->assertCount(2, $records);

        // First record should be adjustment
        $adjustment = json_decode($records[0]['parameter'], true);
        $this->assertEquals(CreditChangeType::BALANCE_ADJUSTMENT->value, $adjustment['reason']);
        $this->assertEquals(150.0, $adjustment['amount']); // 100 (current) - (-50 history) = 150 diff

        // Second record should be migrated legacy record
        $migrated = json_decode($records[1]['parameter'], true);
        $this->assertEquals(CreditChangeType::FLAT_RATE->value, $migrated['reason']);
        $this->assertEquals(-50.0, $migrated['amount']);
        $this->assertEquals(100.0, $migrated['balance']); // Balance after +150 adjustment -50 record = 100
    }

    public function testDeleteZeroAmountRecords(): void
    {
        $userId = 3;
        $this->db->query(
            "INSERT INTO credit (userId, credit) VALUES (:userId, :credit)",
            ['userId' => $userId, 'credit' => 0.0]
        );

        $this->db->query(
            "INSERT INTO history (userId, bikeNum, action, parameter, time)
            VALUES (:userId, 0, :action, :parameter, :time)",
            [
                'userId' => $userId,
                'action' => Action::CREDIT_CHANGE->value,
                'parameter' => '0|',
                'time' => '2023-01-01 10:00:00'
            ]
        );

        $this->commandTester->execute([]);

        $result = $this->db->query("SELECT * FROM history WHERE userId = :userId", ['userId' => $userId]);
        $this->assertEquals(0, $result->rowCount());
    }
}
