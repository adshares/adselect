<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use Adshares\AdSelect\Application\Service\DataCleaner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;

class CleanUserHistory extends Command
{
    use CleanTrait;

    protected static $defaultName = 'ops:es:clean-user-history';

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
            ->setDescription('Remove users\' history documents older than given date (or -2 days by default).')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Older documents than `from` date will be removed. Supported format: Y-m-d H:i:s',
                new DateTime('-2 day')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fromDate = $this->readFrom($input, $output);

        if (!$fromDate) {
            return self::INVALID;
        }

        $output->writeln(sprintf(
            'Start removing documents (user history) older than %s',
            $fromDate->format('Y-m-d H:i:s')
        ));

        $this->dataCleaner->cleanUserHistory($fromDate);

        $output->writeln('Finished removing documents.');
        return self::SUCCESS;
    }
}
