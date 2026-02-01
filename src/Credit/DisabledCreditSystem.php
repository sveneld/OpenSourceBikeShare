<?php

declare(strict_types=1);

namespace BikeShare\Credit;

use BikeShare\Enum\CreditChangeType;

class DisabledCreditSystem implements CreditSystemInterface
{
    public function increaseCredit(int $userId, float $amount, CreditChangeType $type, array $context = []): void
    {
    }

    public function decreaseCredit(int $userId, float $amount, CreditChangeType $type, array $context = []): void
    {
    }

    public function getUserCredit(int $userId): float
    {
        return 0;
    }

    public function getMinRequiredCredit(): float
    {
        return PHP_INT_MAX;
    }

    public function isEnoughCreditForRent(int $userid): bool
    {
        return true;
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function getCreditCurrency(): string
    {
        return '';
    }

    public function getRentalFee(): float
    {
        return 0;
    }

    public function getPriceCycle(): int
    {
        return 0;
    }

    public function getLongRentalFee(): float
    {
        return 0;
    }

    public function getLimitIncreaseFee(): float
    {
        return 0;
    }

    public function getViolationFee(): float
    {
        return 0;
    }

    /**
     * @return array<int, array{date: \DateTimeImmutable, amount: float, type: string, balance: float}>
     */
    public function getUserCreditHistory(int $userId): array
    {
        return [];
    }
}
