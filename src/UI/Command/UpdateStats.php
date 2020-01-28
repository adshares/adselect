<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use Adshares\AdSelect\Application\Service\DataCleaner;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Service\StatsUpdater;
use DateTime;
use Exception;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $toStr = $this->updater->getLastPaidEventTime();
        if (!$toStr) {
            $output->writeln(
                'No events to process'
            );
            return;
        }

        $to = new \DateTimeImmutable($toStr, new \DateTimeZone("UTC"));
        $from = $to->modify('-30 days');

        $this->updater->recalculateRPMStats($from, $to, $input->getOption('threads'));
        $output->writeln(
            sprintf(
                'Finished calculating zone RPM stats using events between %s and %s',
                $from->format(DATE_ISO8601),
                $to->format(DATE_ISO8601)
            )
        );


        $from = $to->modify('-12 hours');

        $this->updater->recalculateAdserverStats($from, $to);
        $output->writeln(
            sprintf(
                'Finished calculating Adserver stats using events between %s and %s',
                $from->format(DATE_ISO8601),
                $to->format(DATE_ISO8601)
            )
        );
    }
}
