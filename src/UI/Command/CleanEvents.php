<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use Adshares\AdSelect\Application\Service\DataCleaner;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanEvents extends Command
{
    use CleanTrait;
    use LockableTrait;

    protected static $defaultName = 'ops:es:clean-events';

    /** @var DataCleaner */
    private $dataCleaner;

    public function __construct(DataCleaner $dataCleaner)
    {
        parent::__construct();
        $this->dataCleaner = $dataCleaner;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Remove events from documents older than given date (or -30 days by default).')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Older documents than `from` date will be removed. Supported format: Y-m-d H:i:s',
                new DateTime('-30 days')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');
            return self::FAILURE;
        }
        $fromDate = $this->readFrom($input, $output);

        if (!$fromDate) {
            return self::INVALID;
        }

        $output->writeln(
            sprintf(
                'Start removing documents (events) older than %s',
                $fromDate->format('Y-m-d H:i:s')
            )
        );

        $this->dataCleaner->cleanEvents($fromDate);

        $output->writeln('Finished removing documents.');
        return self::SUCCESS;
    }
}
