<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIndex extends Command
{
    protected static $defaultName = 'ops:es:create-index';

    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates an index in ElasticSearch engine')
            ->setHelp('This command allows you to create an campaign index in ElasticSearch')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force creation removes indexes (when exist) and creates new ones.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = (bool)$input->getOption('force');
        $this->client->createIndexes($force);
        return self::SUCCESS;
    }
}
