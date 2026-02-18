<?php

declare(strict_types=1);

namespace BikeShare\Rent;

use BikeShare\Rent\Enum\RentSystemType;
use Symfony\Component\DependencyInjection\ServiceLocator;

class RentSystemFactory
{
    public function __construct(
        private readonly ServiceLocator $locator
    ) {
    }

    public function getRentSystem(RentSystemType $type): RentSystemInterface
    {
        if ($this->locator->has($type->value)) {
            return $this->locator->get($type->value);
        }

        throw new \InvalidArgumentException('Invalid rent system type');
    }
}
