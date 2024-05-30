<?php

namespace BikeShare\Rent;

class RentSystemQR extends AbstractRentSystem implements RentSystemInterface
{
    public function rentBike($userId, $bikeId, $force = false)
    {
        $force = false; #rent by qr code can not be forced

        return parent::rentBike($userId, $bikeId, $force);
    }

    public function returnBike($userId, $bikeId, $standName, $note = '', $force = false)
    {
        $force = false; #return by qr code can not be forced
        $note = ''; #note can not be provided via qr code

        if ($bikeId !== 0) {
            $this->logger->error("Bike number could not be provided via QR code", ["userId" => $userId]);
            return $this->response(_('Invalid bike number'), ERROR);
        }

        $result = $this->db->query("SELECT bikeNum FROM bikes WHERE currentUser=$userId ORDER BY bikeNum");
        $bikeNumber = $result->rowCount();
        $bikeId = $result->fetchAssoc()['bikeNum'];

        if ($bikeNumber > 1) {
            $message = _('You have') . ' ' . $bikeNumber . ' '
                . _('rented bikes currently. QR code return can be used only when 1 bike is rented. Please, use web');
            if ($this->connectorsConfig["sms"]) {
                $message .= _(' or SMS');
            }
            $message .= _(' to return the bikes.');

            return $this->response($message, ERROR);
        }

        return parent::returnBike($userId, $bikeId, $standName, $note, $force);
    }

    protected function getRentSystemType()
    {
        return 'qr';
    }
}
