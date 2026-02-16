<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\Credit;

use PHPUnit\Framework\Attributes\DataProvider;
use BikeShare\Credit\CreditSystem;
use BikeShare\Credit\CreditSystemFactory;
use BikeShare\Credit\DisabledCreditSystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class CreditSystemFactoryTest extends TestCase
{
    #[DataProvider('creditSystemDataProvider')]
    public function testGetCreditSystem(
        $isCreditSystemEnabled,
        $expectedSystemClass
    ) {
        $serviceLocatorMock = $this->createMock(ServiceLocator::class);
        $factory = new CreditSystemFactory(
            $serviceLocatorMock,
            $isCreditSystemEnabled
        );

        $serviceLocatorMock->expects($this->once())
            ->method('get')
            ->with($expectedSystemClass)
            ->willReturn($this->createStub($expectedSystemClass));

        $this->assertInstanceOf(
            $expectedSystemClass,
            $factory->getCreditSystem()
        );
    }

    public static function creditSystemDataProvider()
    {
        yield 'disabled credit system' => [
            'isCreditSystemEnabled' => false,
            'expectedSystemClass' => DisabledCreditSystem::class
        ];
        yield 'enabled credit system' => [
            'isCreditSystemEnabled' => true,
            'expectedSystemClass' => CreditSystem::class
        ];
    }
}
