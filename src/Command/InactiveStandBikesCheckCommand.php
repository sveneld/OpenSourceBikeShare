<?php

declare(strict_types=1);

namespace BikeShare\Command;

use BikeShare\Notifier\AdminNotifier;
use BikeShare\Repository\BikeRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:inactive_stand_bikes_check',
    description: 'Notify admins about bikes inactive on non-service stands',
)]
class InactiveStandBikesCheckCommand extends Command
{
    public function __construct(
        private readonly BikeRepository $bikeRepository,
        private readonly AdminNotifier $adminNotifier,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->clock->now();
        $weekThreshold = $now->sub(new \DateInterval('P7D'));
        $isSilent = $output->isQuiet();
        $io = new SymfonyStyle($input, $output);

        $inactiveBikes = $this->bikeRepository->findInactiveBikes($weekThreshold);

        if ([] === $inactiveBikes) {
            if (!$isSilent) {
                $io->writeln('No bikes inactive on non-service stands for more than 7 days.');
            }

            return Command::SUCCESS;
        }

        $lines = [];
        $rows = [];
        foreach ($inactiveBikes as $bike) {
            $lastMoveTime = new \DateTimeImmutable((string)$bike['lastMoveTime']);
            $inactiveDays = (int)$lastMoveTime->diff($now)->days;
            $bikeNumber = (int)$bike['bikeNum'];
            $standName = (string)$bike['standName'];
            $lastMoveFormatted = $lastMoveTime->format('Y-m-d H:i:s');

            $lines[] = sprintf(
                '%d | %s | Last move: %s | Inactive: %d days',
                $bikeNumber,
                $standName,
                $lastMoveFormatted,
                $inactiveDays
            );

            $rows[] = [
                $bikeNumber,
                $standName,
                $lastMoveFormatted,
                $inactiveDays,
            ];
        }

        $this->adminNotifier->notify(
            $this->buildAdminMessage($lines),
            false
        );

        if (!$isSilent) {
            $io->title('Inactive bikes on stands (service stands excluded)');
            $io->table(
                ['Bike', 'Stand', 'Last move', 'Inactive days'],
                $rows
            );

            $io->success(
                sprintf(
                    'Admin notification sent for %d bikes.',
                    count($inactiveBikes)
                )
            );
        }

        return Command::SUCCESS;
    }

    private function buildAdminMessage(array $bikeLines): string
    {
        $lines = [
            'Inactive bikes on stands (service stands excluded, sorted by inactive days).',
            '',
        ];
        foreach ($bikeLines as $bikeLine) {
            $lines[] = '- ' . $bikeLine;
        }

        if ([] === $bikeLines) {
            $lines[] = '- none';
        }

        return implode(PHP_EOL, $lines);
    }
}
