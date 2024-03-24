<?php

namespace Test\BikeShare\Sms;

use BikeShare\Db\DbInterface;
use BikeShare\Sms\SmsSender;
use BikeShare\SmsConnector\SmsConnectorInterface;
use PHPUnit\Framework\TestCase;

class SmsSenderTest extends TestCase
{
    protected function setUp()
    {
        $this->smsConnector = $this->createMock(SmsConnectorInterface::class);
        $this->db = $this->createMock(DbInterface::class);
        $this->smsSender = new SmsSender($this->smsConnector, $this->db);
    }

    protected function tearDown()
    {
        unset(
            $this->smsConnector,
            $this->db,
            $this->smsSender
        );
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend(
        $number,
        $message,
        $smsConnectorCallParams,
        $dbEscapeCallParams,
        $dbEscapeCallResult,
        $dbCallParams
    ) {
        $this->smsConnector
            ->expects($this->exactly(count($smsConnectorCallParams)))
            ->method('send')
            ->withConsecutive(...$smsConnectorCallParams);
        $this->db
            ->expects($this->exactly(count($dbEscapeCallParams)))
            ->method('escape')
            ->withConsecutive(...$dbEscapeCallParams)
            ->willReturnOnConsecutiveCalls(...$dbEscapeCallResult);
        $this->db
            ->expects($this->exactly(count($dbCallParams)))
            ->method('query')
            ->withConsecutive(...$dbCallParams);
        $this->db
            ->expects($this->exactly(count($dbCallParams)))
            ->method('commit');

        $this->smsSender->send($number, $message);
    }

    public function sendDataProvider()
    {
        yield 'short message' => [
            'number' => '123456789',
            'message' => 'Hello, World!',
            'smsConnectorCallParams' => [
                ['123456789', 'Hello, World!']
            ],
            'dbEscapeCallParams' => [['Hello, World!']],
            'dbEscapeCallResult' => ['Hello, World!'],
            'dbCallParams' => [
                ["INSERT INTO sent SET number='123456789', text='Hello, World!'"]
            ]
        ];
        yield 'encoded message' => [
            'number' => '123456789',
            'message' => 'Hello, "World"!',
            'smsConnectorCallParams' => [
                ['123456789', 'Hello, "World"!']
            ],
            'dbEscapeCallParams' => [['Hello, "World"!']],
            'dbEscapeCallResult' => ['Hello, \"World\"!'],
            'dbCallParams' => [
                ["INSERT INTO sent SET number='123456789', text='Hello, \\\"World\\\"!'"]
            ]
        ];
        yield 'long message' => [
            'number' => '123456789',
            'message' => 'Hello, World! Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus '
                . 'euismod mi fermentum sollicitudin. Vivamus euismod, tellus ac euismod       ultricies, justo risus '
                . 'luctus ipsum, quis condimentum orci lacus id tellus. Sed ut ultrices mi. Nullam id orci ut '
                . 'mauris tincidunt tincidunt. ',
            'smsConnectorCallParams' => [
                [
                    '123456789',
                    'Hello, World! Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus '
                    . 'euismod mi fermentum sollicitudin. Vivamus euismod, tellus ac euismod'
                ],
                [
                    '123456789',
                    'ultricies, justo risus luctus ipsum, quis condimentum orci lacus id tellus. Sed ut ultrices mi. '
                    . 'Nullam id orci ut mauris tincidunt tincidunt.'
                ]
            ],
            'dbEscapeCallParams' => [
                [
                    'Hello, World! Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus '
                    . 'euismod mi fermentum sollicitudin. Vivamus euismod, tellus ac euismod'
                ],
                [
                    'ultricies, justo risus luctus ipsum, quis condimentum orci lacus id tellus. Sed ut ultrices mi. '
                    . 'Nullam id orci ut mauris tincidunt tincidunt.'
                ]
            ],
            'dbEscapeCallResult' => [
                'Hello, World! Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus '
                . 'euismod mi fermentum sollicitudin. Vivamus euismod, tellus ac euismod',
                'ultricies, justo risus luctus ipsum, quis condimentum orci lacus id tellus. Sed ut ultrices mi. '
                . 'Nullam id orci ut mauris tincidunt tincidunt.'
            ],
            'dbCallParams' => [
                [
                    "INSERT INTO sent SET number='123456789', text='Hello, World! Lorem ipsum dolor sit amet, "
                    . "consectetur adipiscing elit. Nulla nec purus euismod mi fermentum sollicitudin. "
                    . "Vivamus euismod, tellus ac euismod'"],
                [
                    "INSERT INTO sent SET number='123456789', text='ultricies, justo risus luctus ipsum, quis "
                    . "condimentum orci lacus id tellus. Sed ut ultrices mi. Nullam id orci ut mauris "
                    . "tincidunt tincidunt.'"
                ]
            ]
        ];
    }
}
