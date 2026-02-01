<?php

declare(strict_types=1);

namespace BikeShare\Controller;

use BikeShare\Credit\CreditSystemInterface;
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
        CreditSystemInterface $creditSystem,
        User $user,
    ): Response {
        if (!$creditSystem->isEnabled()) {
            throw $this->createNotFoundException('Credit system is disabled');
        }

        $userId = (int)$user->findUserIdByNumber($this->getUser()->getUserIdentifier());
        $history = $creditSystem->getUserCreditHistory($userId);
        $currentCredit = $creditSystem->getUserCredit($userId);
        $currency = $creditSystem->getCreditCurrency();

        return $this->render('credit/history.html.twig', [
            'history' => $history,
            'currentCredit' => $currentCredit,
            'currency' => $currency,
        ]);
    }
}
