<?php

declare(strict_types=1);

namespace Test\BikeShare\Integration\SmsCommand;

use BikeShare\App\Entity\User;
use BikeShare\Db\DbInterface;
use BikeShare\SmsCommand\CommandExecutor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InfoCommandIntegrationTest extends KernelTestCase
{
    private DbInterface $db;
    private CommandExecutor $executor;

    protected function setUp(): void
    {
        $_ENV['SMS_CONNECTOR'] = 'disabled';
        self::bootKernel();
        $container = static::getContainer();
        $this->db = $container->get(DbInterface::class);
        $this->executor = $container->get(CommandExecutor::class);

        // create table and insert test stand
        $this->db->query('CREATE TABLE IF NOT EXISTS stands (standId INTEGER PRIMARY KEY AUTOINCREMENT, standName VARCHAR(20), standDescription TEXT, standPhoto TEXT, serviceTag INTEGER, placeName TEXT, longitude FLOAT, latitude FLOAT)');
        $this->db->query('DELETE FROM stands');
        $this->db->query("INSERT INTO stands (standId, standName, standDescription, standPhoto, serviceTag, placeName, longitude, latitude) VALUES (1, 'RACKO', 'Main rack', '', 0, 'Central', 17.12345, 48.98765)");
    }

    public function testInfoReturnsStandDetails(): void
    {
        $user = new User(1, '123456', 'u@example.com', 'pw', 'city', 'name', 0, true);
        $response = $this->executor->execute('INFO RACKO', $user);
        $this->assertSame('RACKO - Main rack, GPS: 48.98765,17.12345', $response);
    }
}
