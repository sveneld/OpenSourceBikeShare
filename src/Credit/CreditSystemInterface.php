<?php

declare(strict_types=1);

namespace BikeShare\Credit;

use BikeShare\Enum\CreditChangeType;

interface CreditSystemInterface
{
    public function increaseCredit(int $userId, float $amount, CreditChangeType $type, array $context = []): void;

    public function decreaseCredit(int $userId, float $amount, CreditChangeType $type, array $context = []): void;

    public function getUserCredit(int $userId): float;

    public function getMinRequiredCredit(): float;

    public function isEnoughCreditForRent(int $userid): bool;

    public function isEnabled(): bool;

    public function getCreditCurrency(): string;

    public function getRentalFee(): float;

    public function getPriceCycle(): int;

    public function getLongRentalFee(): float;

    public function getLimitIncreaseFee(): float;

    public function getViolationFee(): float;

    /**
     * @return array<int, array{date: \DateTimeImmutable, amount: float, type: string, balance: float}>
     */
    public function getUserCreditHistory(int $userId): array;
}
