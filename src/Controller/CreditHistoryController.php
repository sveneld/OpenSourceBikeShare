<?php

declare(strict_types=1);

namespace BikeShare\Controller;

use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Repository\HistoryRepository;
use BikeShare\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreditHistoryController extends AbstractController
{
    #[Route(
        path: '/credit/history',
        name: 'credit_history',
        methods: ['GET'],
    )]
    public function history(
        HistoryRepository $historyRepository,
        CreditSystemInterface $creditSystem,
        User $user,
    ): Response {
        if (!$creditSystem->isEnabled()) {
            throw $this->createNotFoundException('Credit system is disabled');
        }

        $userId = $user->findUserIdByNumber($this->getUser()->getUserIdentifier());
        $history = $historyRepository->findCreditHistoryByUser((int)$userId);
        $currentCredit = $creditSystem->getUserCredit((int)$userId);
        $currency = $creditSystem->getCreditCurrency();

        $parsedHistory = $this->parseHistory($history);

        return $this->render('credit/history.html.twig', [
            'history' => $parsedHistory,
            'currentCredit' => $currentCredit,
            'currency' => $currency,
        ]);
    }

    /**
     * Parse credit history entries into a structured format.
     */
    private function parseHistory(array $history): array
    {
        $parsed = [];

        foreach ($history as $entry) {
            $item = [
                'id' => $entry['id'],
                'time' => $entry['time'],
                'action' => $entry['action'],
                'amount' => null,
                'balance' => null,
                'description' => '',
                'type' => 'neutral', // 'positive', 'negative', 'neutral'
            ];

            if ($entry['action'] === 'CREDIT') {
                // CREDIT action stores current balance
                $item['balance'] = (float)$entry['parameter'];
                $item['description'] = 'Balance update';
            } elseif ($entry['action'] === 'CREDITCHANGE') {
                // CREDITCHANGE format: "amount|breakdown" e.g. "10|overfree-5;flat-5;"
                $parts = explode('|', $entry['parameter']);
                $amount = (float)$parts[0];
                $item['amount'] = $amount;
                $item['type'] = $amount >= 0 ? 'positive' : 'negative';

                if (isset($parts[1])) {
                    $item['description'] = $this->parseBreakdown($parts[1]);
                }

                // Check for coupon
                if (isset($parts[2])) {
                    $item['coupon'] = $parts[2];
                }
            }

            $parsed[] = $item;
        }

        return $parsed;
    }

    /**
     * Parse breakdown string into human-readable description.
     */
    private function parseBreakdown(string $breakdown): string
    {
        $descriptions = [];

        // Handle add+ format (adding credit)
        if (str_starts_with($breakdown, 'add+')) {
            return 'Credit added';
        }

        // Parse fee breakdown like "overfree-5;flat-5;"
        $components = explode(';', rtrim($breakdown, ';'));
        foreach ($components as $component) {
            if (empty($component)) {
                continue;
            }

            // Format: type-amount or type+amount
            if (preg_match('/^(\w+)([-+])(\d+(?:\.\d+)?)$/', $component, $matches)) {
                $type = $matches[1];
                $sign = $matches[2];
                $value = $matches[3];

                $descriptions[] = match ($type) {
                    'overfree' => "Over free time: {$sign}{$value}",
                    'flat' => "Flat rate: {$sign}{$value}",
                    'long' => "Long rental: {$sign}{$value}",
                    'longstandbonus' => "Long stand bonus: +{$value}",
                    default => "{$type}: {$sign}{$value}",
                };
            } elseif (str_starts_with($component, 'longstandbonus+')) {
                $value = substr($component, strlen('longstandbonus+'));
                $descriptions[] = "Long stand bonus: +{$value}";
            }
        }

        return implode(', ', $descriptions) ?: $breakdown;
    }
}
