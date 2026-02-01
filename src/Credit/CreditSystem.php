<?php

declare(strict_types=1);

namespace BikeShare\Credit;

use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use BikeShare\Enum\CreditChangeType;
use BikeShare\Repository\HistoryRepository;
use DateTimeImmutable;

class CreditSystem implements CreditSystemInterface
{
    // false = no credit system and Exceptions will be thrown
    // true = apply credit system rules and deductions
    private readonly bool $isEnabled;
    // currency used for credit system
    private readonly string $creditCurrency;
    // minimum credit required to allow any bike operations
    private readonly float $minBalanceCredit;
    // rental fee (after WATCHES_FREE_TIME)
    private readonly float $rentalFee;
    // 0 = disabled,
    // 1 = charge flat price CREDIT_SYSTEM_RENTAL_FEE every WATCHES_FLAT_PRICE_CYCLE minutes,
    // 2 = charge doubled price CREDIT_SYSTEM_RENTAL_FEE every WATCHES_DOUBLE_PRICE_CYCLE minutes
    private readonly int $priceCycle;
    // long rental fee (WATCHES_LONG_RENTAL time)
    private readonly float $longRentalFee;
    // credit needed to temporarily increase limit, applicable only when USER_BIKE_LIMIT_INCREASE > 0
    private readonly float $limitIncreaseFee;
    // credit deduction for rule violations (applied by admins)
    private readonly float $violationFee;

    public function __construct(
        bool $isEnabled,
        string $creditCurrency,
        float $minBalanceCredit,
        float $rentalFee,
        int $priceCycle,
        float $longRentalFee,
        float $limitIncreaseFee,
        float $violationFee,
        private readonly DbInterface $db,
        private readonly HistoryRepository $historyRepository,
    ) {
        if (!$isEnabled) {
            throw new \RuntimeException('Use DisabledCreditSystem instead');
        }

        if (
            $minBalanceCredit < 0
            || $rentalFee < 0
            || $longRentalFee < 0
            || $limitIncreaseFee < 0
            || $violationFee < 0
        ) {
            throw new \InvalidArgumentException('Credit values cannot be negative');
        }

        if (!in_array($priceCycle, [0, 1, 2], true)) {
            throw new \InvalidArgumentException('Invalid price cycle value');
        }

        $this->isEnabled = $isEnabled;
        $this->creditCurrency = $creditCurrency;
        $this->minBalanceCredit = $minBalanceCredit;
        $this->rentalFee = $rentalFee;
        $this->priceCycle = $priceCycle;
        $this->longRentalFee = $longRentalFee;
        $this->limitIncreaseFee = $limitIncreaseFee;
        $this->violationFee = $violationFee;
    }

    public function increaseCredit(int $userId, float $amount, CreditChangeType $type, array $context = []): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be non-negative');
        }

        if ($amount === 0.0) {
            return;
        }

        $this->db->query(
            'INSERT INTO credit (userId, credit) 
                   VALUES (:userId, :creditAmount)
                   ON DUPLICATE KEY UPDATE credit = credit + :creditAmountUpdate',
            [
                'userId' => $userId,
                'creditAmount' => $amount,
                'creditAmountUpdate' => $amount,
            ]
        );

        $newBalance = $this->getUserCredit($userId);

        $parameter = json_encode([
            'amount' => $amount,
            'balance' => $newBalance,
            'reason' => $type->value,
            ...(!empty($context['couponCode']) ? ['couponCode' => $context['couponCode']] : []),
        ], JSON_THROW_ON_ERROR);

        $this->historyRepository->addItem(
            $userId,
            0,
            Action::CREDIT_CHANGE,
            $parameter
        );
    }

    public function decreaseCredit(int $userId, float $amount, CreditChangeType $type, array $context = []): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be non-negative');
        }

        if ($amount === 0.0) {
            return;
        }

        $this->db->query(
            'UPDATE credit SET credit = credit - :credit WHERE userId = :userId',
            [
                'userId' => $userId,
                'credit' => $amount
            ]
        );

        $newBalance = $this->getUserCredit($userId);

        $parameter = json_encode([
            'amount' => -$amount,
            'balance' => $newBalance,
            'reason' => $type->value,
            ...(!empty($context['couponCode']) ? ['couponCode' => $context['couponCode']] : []),
        ], JSON_THROW_ON_ERROR);

        $this->historyRepository->addItem(
            $userId,
            0,
            Action::CREDIT_CHANGE,
            $parameter
        );
    }

    public function getUserCredit(int $userId): float
    {
        $result = $this->db->query('SELECT credit FROM credit WHERE userId = :userId', ['userId' => $userId]);
        if ($result->rowCount() == 0) {
            return 0;
        }

        return (float)$result->fetchAssoc()['credit'];
    }

    public function getMinRequiredCredit(): float
    {
        return $this->minBalanceCredit + $this->rentalFee + $this->longRentalFee;
    }

    public function isEnoughCreditForRent(int $userid): bool
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

    public function getRentalFee(): float
    {
        return $this->rentalFee;
    }

    public function getPriceCycle(): int
    {
        return $this->priceCycle;
    }

    public function getLongRentalFee(): float
    {
        return $this->longRentalFee;
    }

    public function getLimitIncreaseFee(): float
    {
        return $this->limitIncreaseFee;
    }

    public function getViolationFee(): float
    {
        return $this->violationFee;
    }

    /**
     * @return array<int, array{date: \DateTimeImmutable, amount: float, type: string, balance: float}>
     */
    public function getUserCreditHistory(int $userId): array
    {
        $history = $this->historyRepository->findCreditHistoryByUser($userId, 1000);
        $parsed = [];

        foreach ($history as $entry) {
            $date = new DateTimeImmutable($entry['time']);
            $parameter = $entry['parameter'];

            $jsonData = json_decode($parameter, true);
            $parsed[] = [
                'date' => $date,
                'amount' => (float)($jsonData['amount'] ?? 0),
                'type' => CreditChangeType::tryFrom($jsonData['reason'])?->value ?? 'unknown',
                'balance' => (float)($jsonData['balance'] ?? 0),
            ];
        }

        return $parsed;
    }
}
