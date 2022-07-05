<?php

declare(strict_types=1);

namespace App\UI\Command;

use App\Infrastructure\ElasticSearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIndex extends Command
{
    protected static $defaultName = 'ops:es:create-index';

    private Client $client;

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
