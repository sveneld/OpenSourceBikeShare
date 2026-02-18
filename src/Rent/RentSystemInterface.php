<?php

namespace BikeShare\Rent;

use BikeShare\Rent\DTO\RentSystemResult;
use BikeShare\Rent\Enum\RentSystemType;

interface RentSystemInterface
{
    public function rentBike($userId, $bikeId, $force = false): RentSystemResult;

    public function returnBike($userId, $bikeId, $standName, $note = '', $force = false): RentSystemResult;

    public function revertBike($userId, $bikeId): RentSystemResult;

    public static function getType(): RentSystemType;
}
