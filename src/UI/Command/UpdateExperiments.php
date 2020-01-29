<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use Adshares\AdSelect\Application\Service\DataCleaner;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Service\ExperimentsUpdater;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Service\StatsUpdater;
use DateTime;
use Exception;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\FlockStore;

class UpdateExperiments extends Command
{
    protected static $defaultName = 'ops:es:update-exp';

    /** @var ExperimentsUpdater */
    private $updater;

    public function __construct(ExperimentsUpdater $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }

    protected function configure(): void
    {
        $this->setDescription('Update experiments stats for all campaigns')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Consider events since specified date. Supported format: Y-m-d H:i:s. Defaults to 3 hours ageo.',
                new DateTime('-3 hours')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new Lock(new Key($this->getName()), new FlockStore(), null, false);
        if (!$lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $from = $input->getOption('from');

        $this->updater->recalculateExperiments($from);

        $output->writeln(
            sprintf(
                'Finished calculating experiments from %s',
                $from->format(DATE_ISO8601)
            )
        );
    }
}
