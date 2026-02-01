<?php

declare(strict_types=1);

namespace BikeShare\EventListener;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Event\BikeReturnEvent;
use BikeShare\Repository\HistoryRepository;
use BikeShare\Repository\StandRepository;
use Symfony\Component\Clock\ClockInterface;

class LongStandBonusEventListener
{
    public function __construct(
        private readonly int $longStandDays,
        private readonly float $longStandBonus,
        private readonly HistoryRepository $historyRepository,
        private readonly StandRepository $standRepository,
        private readonly CreditSystemInterface $creditSystem,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(BikeReturnEvent $event): void
    {
        if ($this->longStandDays <= 0) {
            return;
        }

        $lastReturn = $this->historyRepository->findPreviousBikeReturn($event->getBikeNumber());
        if ($lastReturn === null) {
            return;
        }

        $previousStandId = (int) $lastReturn['standId'];
        $currentStand = $this->standRepository->findItemByName($event->getStandName());

        if ($currentStand === null) {
            return;
        }

        $currentStandId = (int) $currentStand['standId'];

        if ($previousStandId === $currentStandId) {
            return;
        }

        $lastReturnTime = new \DateTimeImmutable($lastReturn['time']);
        $now = $this->clock->now();
        $daysDiff = $now->diff($lastReturnTime)->days;

        if ($daysDiff >= $this->longStandDays) {
            $this->creditSystem->addCredit($event->getUserId(), $this->longStandBonus);
        }
    }
}
