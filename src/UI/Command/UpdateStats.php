<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Service\StatsUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\FlockStore;

class UpdateStats extends Command
{
    protected static $defaultName = 'ops:es:update-stats';

    /** @var StatsUpdater */
    private $updater;

    public function __construct(StatsUpdater $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }

    protected function configure(): void
    {
        $this->setDescription('Update RPM stats for all campaigns and all zones')->addOption(
            'threads',
            't',
            InputOption::VALUE_OPTIONAL,
            'How many threads to run',
            1
        );
    }

    private function partitionCampaigns($n)
    {
        $perThread = 256 / $n;

        for ($i = 0; round($i) < 256; $i += $perThread) {
            $min = round($i);
            $max = round($i + $perThread);
            $campaignRange = [
                'gte' => \str_pad(\dechex($min), 2, "0", STR_PAD_LEFT),
            ];
            if ($max < 256) {
                $campaignRange['lt'] = \str_pad(\dechex($max), 2, "0", STR_PAD_LEFT);
            }

            yield $campaignRange;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = new Lock(new Key($this->getName()), new FlockStore(), null, false);
        if (!$lock->acquire()) {
            $output->writeln('The command is already running in another process.');
            return self::FAILURE;
        }
        $threads = $input->getOption('threads');

        $is_child = false;
        $is_parent = false;
        $campaignRange = null;

        if ($threads > 1) {
            $threads = min(16, $threads);

            $nJobs = 0;
            foreach ($this->partitionCampaigns(4 * $threads) as $range) {
                if ($nJobs >= $threads) {
                    echo "waiting for free thread...\n";
                    \pcntl_wait($pid);
                    $nJobs--;
                }
                $pid = \pcntl_fork();
                if ($pid === 0) {
                    $is_child = true;
                    $campaignRange = $range;
                    break;
                } else {
                    $nJobs++;
                    printf("started sub job pid=%d range=%s\n", $pid, json_encode($range));
                }
            }

            if (!$is_child) {
                while ($nJobs > 0) {
                    $pid = 0;
                    \pcntl_wait($pid);
                    $nJobs--;
                }
                echo "done all\n";
                $is_parent = true;
            }
        }

        $toStr = $this->updater->getLastPaidEventTime();
        if (!$toStr) {
            $output->writeln(
                'No events to process'
            );
            return self::SUCCESS;
        }

        $to = new \DateTimeImmutable($toStr, new \DateTimeZone("UTC"));
        $from = $to->modify('-30 days');

        if (!$is_parent) {
            $this->updater->recalculateRPMStats($from, $to, $campaignRange);

            if ($is_child) {
                printf("finished range=%s\n", json_encode($campaignRange));
                exit;
            }
        }

        $output->writeln(
            sprintf(
                'Finished calculating zone RPM stats using events between %s and %s',
                $from->format(DATE_ISO8601),
                $to->format(DATE_ISO8601)
            )
        );

        $this->updater->removeStaleRPMStats();
        return self::SUCCESS;
    }
}
