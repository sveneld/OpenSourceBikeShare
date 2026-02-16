<?php

declare(strict_types=1);

namespace BikeShare\Test\Integration\Command;

use BikeShare\Db\DbInterface;
use BikeShare\Test\Integration\BikeSharingKernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PasswordHashStatsCommandTest extends BikeSharingKernelTestCase
{
    private CommandTester $commandTester;
    private DbInterface $db;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:password_hash_stats');
        $this->commandTester = new CommandTester($command);

        $this->db = self::getContainer()->get(DbInterface::class);
    }

    public function testExecuteOutputsHashStats(): void
    {
        $stats = $this->db->query(
            <<<'SQL'
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN password REGEXP '^[a-f0-9]{128}$' THEN 1 ELSE 0 END) AS legacy_sha512,
                SUM(CASE
                    WHEN password LIKE '$2y$%' OR password LIKE '$argon2id$%' OR password LIKE '$argon2i$%'
                    THEN 1
                    ELSE 0
                END) AS modern_hash
            FROM users
            SQL
        )->fetchAssoc();

        $total = (int)($stats['total'] ?? 0);
        $legacy = (int)($stats['legacy_sha512'] ?? 0);
        $modern = (int)($stats['modern_hash'] ?? 0);
        $unknown = max(0, $total - $legacy - $modern);
        $legacyPercentage = $total > 0 ? ($legacy * 100 / $total) : 0.0;

        $this->commandTester->execute([]);
        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Password Hash Migration Stats', $output);
        $this->assertStringContainsString('Total users: ' . $total, $output);
        $this->assertStringContainsString('Legacy SHA-512 hashes: ' . $legacy, $output);
        $this->assertStringContainsString('Modern hashes: ' . $modern, $output);
        $this->assertStringContainsString('Unknown format hashes: ' . $unknown, $output);
        $this->assertStringContainsString(
            'Legacy hash percentage: ' . number_format($legacyPercentage, 2, '.', '') . '%',
            $output
        );
        $this->assertStringContainsString('Recommendation:', $output);
    }
}
