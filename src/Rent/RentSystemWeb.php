<?php

namespace BikeShare\Rent;

use BikeShare\Rent\Enum\RentSystemType;

class RentSystemWeb extends AbstractRentSystem implements RentSystemInterface
{
    public static function getType(): RentSystemType
    {
        return RentSystemType::WEB;
    }
}
