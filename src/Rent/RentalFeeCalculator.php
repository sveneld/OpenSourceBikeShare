<?php

declare(strict_types=1);

namespace BikeShare\Rent;

use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use Symfony\Component\Clock\ClockInterface;

class RentalFeeCalculator
{
    // 0 = disabled,
    // 1 = charge flat price CREDIT_SYSTEM_RENTAL_FEE every WATCHES_FLAT_PRICE_CYCLE minutes,
    // 2 = charge doubled price CREDIT_SYSTEM_RENTAL_FEE every WATCHES_DOUBLE_PRICE_CYCLE minutes

    public function __construct(
        private readonly DbInterface $db,
        private readonly ClockInterface $clock,
        private readonly array $watchesConfig,
        private readonly float $rentalFee,
        private readonly float $longRentalFee,
        private readonly int $priceCycle,
    ) {
        if (
            $rentalFee < 0
            || $longRentalFee < 0
        ) {
            throw new \InvalidArgumentException('Fee values cannot be negative');
        }
        if (!in_array($priceCycle, [0, 1, 2], true)) {
            throw new \InvalidArgumentException('Invalid price cycle value');
        }
    }

    public function getMinRequiredCredit(): float
    {
        return $this->rentalFee + $this->longRentalFee;
    }

    public function changeCreditEndRental(int $bike, int $userId): ?array
    {
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
                $creditChange += $this->rentalFee;
                $changeLog .= 'rerent-' . $this->rentalFee . ';';
            }
        }

        if ($timeDiff > $this->watchesConfig['freetime'] * 60) {
            $creditChange += $this->rentalFee;
            $changeLog .= 'overfree-' . $this->rentalFee . ';';
        }

        $freeTime = $this->watchesConfig['freetime'] == 0 ? 1 : $this->watchesConfig['freetime'];

        if ($this->priceCycle && $timeDiff > $freeTime * 60 * 2) {
            $tempTimeDiff = $timeDiff - ($freeTime * 60 * 2);
            if ($this->priceCycle == 1) {
                $cycles = (int) ceil($tempTimeDiff / ($this->watchesConfig['flatpricecycle'] * 60));
                $creditChange += $this->rentalFee * $cycles;
                $changeLog .= 'flat-' . $this->rentalFee * $cycles . ';';
            } elseif ($this->priceCycle == 2) {
                $cycles = (int) ceil($tempTimeDiff / ($this->watchesConfig['doublepricecycle'] * 60));
                $tempCreditRent = $this->rentalFee;
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
            $creditChange += $this->longRentalFee;
            $changeLog .= 'longrent-' . $this->longRentalFee . ';';
        }

        return ['creditChange' => $creditChange, 'changeLog' => $changeLog];
    }
}
