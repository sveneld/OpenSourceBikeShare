<?php

declare(strict_types=1);

namespace BikeShare\Command;

use BikeShare\Db\DbInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'app:password_hash_stats', description: 'Show password hash migration statistics')]
class PasswordHashStatsCommand extends Command
{
    public function __construct(
        private readonly DbInterface $db,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stats = $this->db->query(
            <<<'SQL'
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN password REGEXP '^[a-f0-9]{128}$' THEN 1 ELSE 0 END) AS legacy_sha512,
                SUM(CASE
                    WHEN password LIKE '$2y$%' OR password LIKE '$argon2id$%' OR password LIKE '$argon2i$%'
                    THEN 1
                    ELSE 0
                END) AS modern_hash
            FROM users
            SQL
        )->fetchAssoc();

        if ($stats === false) {
            $io->error('Unable to load password hash statistics. Please check database connectivity and try again.');

            return Command::FAILURE;
        }

        if ([] === $stats) {
            $io->warning('No users found in the database.');

            return Command::SUCCESS;
        }

        $total = $stats['total'];
        $legacy = $stats['legacy_sha512'];
        $modern = $stats['modern_hash'];
        $unknown = max(0, $total - $legacy - $modern);
        $legacyPercentage = $total > 0 ? ($legacy * 100 / $total) : 0.0;

        $io->title('Password Hash Migration Stats');
        $io->writeln('Total users: ' . $total);
        $io->writeln('Legacy SHA-512 hashes: ' . $legacy);
        $io->writeln('Modern hashes: ' . $modern);
        $io->writeln('Unknown format hashes: ' . $unknown);
        $io->writeln('Legacy hash percentage: ' . number_format($legacyPercentage, 2, '.', '') . '%');

        if ($total === 0) {
            $io->note('No users found.');

            return Command::SUCCESS;
        }

        if ($legacyPercentage <= 5.0) {
            $io->success('Recommendation: you can consider forcing password reset for remaining legacy users.');
        } else {
            $io->note('Recommendation: keep backward compatibility enabled until legacy percentage drops further.');
        }

        return Command::SUCCESS;
    }
}
