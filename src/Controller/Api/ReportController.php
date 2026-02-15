<?php

declare(strict_types=1);

namespace BikeShare\Controller\Api;

use BikeShare\Repository\BikeRepository;
use BikeShare\Repository\HistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReportController extends AbstractController
{
    #[Route('/api/report/daily', name: 'api_report_daily', methods: ['GET'])]
    public function daily(
        HistoryRepository $historyRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = $historyRepository->dailyStats();

        return $this->json($stats);
    }

    #[Route('/api/report/user/{year}', name: 'api_report_user', requirements: ['year' => '\d+'], methods: ['GET'])]
    public function user(
        HistoryRepository $historyRepository,
        ClockInterface $clock,
        $year = null,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (is_null($year)) {
            $year = (int)$clock->now()->format('Y');
        } elseif (
            $year > (int)$clock->now()->format('Y')
            || $year < 2010
        ) {
            return $this->json([], Response::HTTP_BAD_REQUEST);
        }

        $stats = $historyRepository->userStats((int)$year);

        return $this->json($stats);
    }

    #[Route('/api/report/inactiveBikes', name: 'api_report_inactive_bikes', methods: ['GET'])]
    public function inactiveBikes(
        BikeRepository $bikeRepository,
        ClockInterface $clock
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $now = $clock->now();
        $weekThreshold = $now->sub(new \DateInterval('P7D'));

        $inactiveBikes = $bikeRepository->findInactiveBikes($weekThreshold);

        $result = [];
        foreach ($inactiveBikes as $bike) {
            $lastMoveTime = new \DateTimeImmutable((string)$bike['lastMoveTime']);
            $inactiveDays = (int)$lastMoveTime->diff($now)->days;

            $result[] = [
                'bikeNum' => (int)$bike['bikeNum'],
                'standName' => (string)$bike['standName'],
                'lastMoveTime' => $lastMoveTime->format('Y-m-d H:i:s'),
                'inactiveDays' => $inactiveDays,
            ];
        }

        return $this->json($result);
    }
}
