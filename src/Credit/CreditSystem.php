<?php

declare(strict_types=1);

namespace BikeShare\Credit;

use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use BikeShare\Rent\RentalFeeCalculator;
use BikeShare\Repository\HistoryRepository;

class CreditSystem implements CreditSystemInterface
{
    // false = no credit system and Exceptions will be thrown
    // true = apply credit system rules and deductions
    private readonly bool $isEnabled;
    // currency used for credit system
    private readonly string $creditCurrency;
    // minimum credit required to allow any bike operations
    private readonly float $minBalanceCredit;
    // credit needed to temporarily increase limit, applicable only when USER_BIKE_LIMIT_INCREASE > 0
    private readonly float $limitIncreaseFee;
    // credit deduction for rule violations (applied by admins)
    private readonly float $violationFee;

    public function __construct(
        bool $isEnabled,
        string $creditCurrency,
        float $minBalanceCredit,
        float $limitIncreaseFee,
        float $violationFee,
        private readonly DbInterface $db,
        private readonly HistoryRepository $historyRepository,
        private readonly RentalFeeCalculator $rentalFeeCalculator,
    ) {
        if (!$isEnabled) {
            throw new \RuntimeException('Use DisabledCreditSystem instead');
        }

        if (
            $minBalanceCredit < 0
            || $limitIncreaseFee < 0
            || $violationFee < 0
        ) {
            throw new \InvalidArgumentException('Credit values cannot be negative');
        }

        $this->isEnabled = $isEnabled;
        $this->creditCurrency = $creditCurrency;
        $this->minBalanceCredit = $minBalanceCredit;
        $this->limitIncreaseFee = $limitIncreaseFee;
        $this->violationFee = $violationFee;
    }

    public function addCredit(int $userId, float $creditAmount, ?string $coupon = null): void
    {
        if ($creditAmount < 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        $this->db->query(
            'INSERT INTO credit (userId, credit) 
                   VALUES (:userId, :creditAmount)
                   ON DUPLICATE KEY UPDATE credit = credit + :creditAmountUpdate',
            [
                'userId' => $userId,
                'creditAmount' => $creditAmount,
                'creditAmountUpdate' => $creditAmount,
            ]
        );

        if ($creditAmount !== 0) {
            $this->historyRepository->addItem(
                $userId,
                0, //BikeNum
                Action::CREDIT_CHANGE,
                $creditAmount . '|add+' . $creditAmount . ($coupon ? '|' . $coupon : '') //parameter
            );
        }
    }

    public function useCredit(int $userId, float $creditAmount): void
    {
        if ($creditAmount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        $currentCredit = $this->getUserCredit($userId);
        if ($currentCredit < $creditAmount) {
            throw new \RuntimeException('Insufficient credit for this operation');
        }

        $this->db->query(
            'UPDATE credit SET credit = credit - :credit WHERE userId = :userId',
            [
                'userId' => $userId,
                'credit' => $creditAmount
            ]
        );
    }

    public function getUserCredit($userId): float
    {
        $result = $this->db->query('SELECT credit FROM credit WHERE userId = :userId', ['userId' => $userId]);
        if ($result->rowCount() == 0) {
            return 0;
        }

        return $result->fetchAssoc()['credit'];
    }

    public function getMinRequiredCredit(): float
    {
        return $this->minBalanceCredit + $this->rentalFeeCalculator->getMinRequiredCredit();
    }

    public function isEnoughCreditForRent($userid): bool
    {
        return $this->getUserCredit($userid) >= $this->getMinRequiredCredit();
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getCreditCurrency(): string
    {
        return $this->creditCurrency;
    }

    public function getLimitIncreaseFee(): float
    {
        return $this->limitIncreaseFee;
    }

    public function getViolationFee(): float
    {
        return $this->violationFee;
    }
}
