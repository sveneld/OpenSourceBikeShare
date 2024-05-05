<?php

namespace BikeShare\Rent;

interface RentSystemInterface
{
    public function rentBike($userId, $bikeId, $force = false);

    public function returnBike($userId, $bikeId, $standName, $note = '', $force = false);
}