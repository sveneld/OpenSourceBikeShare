<?php

declare(strict_types=1);

namespace Test\BikeShare\Integration\Sms;

use BikeShare\Db\DbInterface;
use BikeShare\Sms\SmsSenderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SmsSenderIntegrationTest extends KernelTestCase
{
    private DbInterface $db;
    private SmsSenderInterface $smsSender;

    protected function setUp(): void
    {
        $_ENV['SMS_CONNECTOR'] = 'disabled';
        self::bootKernel();
        $container = static::getContainer();
        $this->db = $container->get(DbInterface::class);
        $this->smsSender = $container->get(SmsSenderInterface::class);

        // create schema for tests
        $this->db->query('CREATE TABLE IF NOT EXISTS sent (time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, number VARCHAR(20) NOT NULL, text VARCHAR(200) NOT NULL)');
        $this->db->query('DELETE FROM sent');
    }

    public function testSendInsertsSingleRow(): void
    {
        $this->smsSender->send('123456789', 'Simple message');
        $rows = $this->db->query('SELECT number, text FROM sent')->fetchAllAssoc();
        $this->assertSame([
            ['number' => '123456789', 'text' => 'Simple message'],
        ], $rows);
    }

    public function testLongMessageIsSplit(): void
    {
        $long = str_repeat('A', 170);
        $this->smsSender->send('987654321', $long);

        $rows = $this->db->query('SELECT number, text FROM sent ORDER BY time')->fetchAllAssoc();
        $this->assertCount(2, $rows);
        $this->assertSame('987654321', $rows[0]['number']);
        $this->assertSame(substr($long, 0, 160), $rows[0]['text']);
        $this->assertSame(substr($long, 160), $rows[1]['text']);
    }
}
