<?php

declare(strict_types=1);

namespace BikeShare\Rent;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use Symfony\Component\Clock\ClockInterface;

class RentalFeeCalculator
{
    public function __construct(
        private readonly CreditSystemInterface $creditSystem,
        private readonly DbInterface $db,
        private readonly ClockInterface $clock,
        private readonly array $watchesConfig,
    ) {
    }

    public function changeCreditEndRental(int $bike, int $userId): ?float
    {
        if ($this->creditSystem->isEnabled() === false) {
            return null;
        }

        $userCredit = $this->creditSystem->getUserCredit($userId);

        $result = $this->db->query(
            'SELECT time FROM history WHERE bikeNum = :bikeNum AND userId = :userId AND action IN (:rentAction, :forceRentAction) ORDER BY time DESC LIMIT 1',
            [
                'bikeNum' => $bike,
                'userId' => $userId,
                'rentAction' => Action::RENT->value,
                'forceRentAction' => Action::FORCE_RENT->value,
            ]
        );

        if ($result->rowCount() !== 1) {
            return null;
        }

        $row = $result->fetchAssoc();
        $startTime = new \DateTimeImmutable($row['time']);
        $endTime = $this->clock->now();
        $timeDiff = $endTime->getTimestamp() - $startTime->getTimestamp();
        $creditChange = 0.0;
        $changeLog = '';

        $oldReturn = $this->db->query(
            'SELECT time FROM history WHERE bikeNum = :bikeNum AND userId = :userId AND action IN (:returnAction, :forceReturnAction) ORDER BY time DESC LIMIT 1',
            [
                'bikeNum' => $bike,
                'userId' => $userId,
                'returnAction' => Action::RETURN->value,
                'forceReturnAction' => Action::FORCE_RETURN->value,
            ]
        );

        if ($oldReturn->rowCount() === 1) {
            $oldRow = $oldReturn->fetchAssoc();
            $returnTime = new \DateTimeImmutable($oldRow['time']);
            if (($startTime->getTimestamp() - $returnTime->getTimestamp()) < 10 * 60 && $timeDiff > 5 * 60) {
                $creditChange += $this->creditSystem->getRentalFee();
                $changeLog .= 'rerent-' . $this->creditSystem->getRentalFee() . ';';
            }
        }

        if ($timeDiff > $this->watchesConfig['freetime'] * 60) {
            $creditChange += $this->creditSystem->getRentalFee();
            $changeLog .= 'overfree-' . $this->creditSystem->getRentalFee() . ';';
        }

        $freeTime = $this->watchesConfig['freetime'] == 0 ? 1 : $this->watchesConfig['freetime'];

        if ($this->creditSystem->getPriceCycle() && $timeDiff > $freeTime * 60 * 2) {
            $tempTimeDiff = $timeDiff - ($freeTime * 60 * 2);
            if ($this->creditSystem->getPriceCycle() == 1) {
                $cycles = (int) ceil($tempTimeDiff / ($this->watchesConfig['flatpricecycle'] * 60));
                $creditChange += $this->creditSystem->getRentalFee() * $cycles;
                $changeLog .= 'flat-' . $this->creditSystem->getRentalFee() * $cycles . ';';
            } elseif ($this->creditSystem->getPriceCycle() == 2) {
                $cycles = (int) ceil($tempTimeDiff / ($this->watchesConfig['doublepricecycle'] * 60));
                $tempCreditRent = $this->creditSystem->getRentalFee();
                for ($i = 1; $i <= $cycles; $i++) {
                    $multiplier = $i;
                    if ($multiplier > $this->watchesConfig['doublepricecyclecap']) {
                        $multiplier = $this->watchesConfig['doublepricecyclecap'];
                    }

                    if ($tempCreditRent == 1) {
                        $tempCreditRent = 2;
                    }

                    $creditChange += pow($tempCreditRent, $multiplier);
                    $changeLog .= 'double-' . pow($tempCreditRent, $multiplier) . ';';
                }
            }
        }

        if ($timeDiff > $this->watchesConfig['longrental'] * 3600) {
            $creditChange += $this->creditSystem->getLongRentalFee();
            $changeLog .= 'longrent-' . $this->creditSystem->getLongRentalFee() . ';';
        }

        $userCredit -= $creditChange;
        if ($creditChange > 0) {
            $this->creditSystem->useCredit($userId, $creditChange);
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->db->query(
            'INSERT INTO history SET userId = :userId, bikeNum = :bikeNum, action = :action, parameter = :creditChange, time = :time',
            [
                'userId' => $userId,
                'bikeNum' => $bike,
                'action' => Action::CREDIT_CHANGE->value,
                'creditChange' => $creditChange . '|' . $changeLog,
                'time' => $now,
            ]
        );

        $this->db->query(
            'INSERT INTO history SET userId = :userId, bikeNum = :bikeNum, action = :action, parameter = :userCredit, time = :time',
            [
                'userId' => $userId,
                'bikeNum' => $bike,
                'action' => Action::CREDIT->value,
                'userCredit' => $userCredit,
                'time' => $now,
            ]
        );

        return $creditChange;
    }
}
