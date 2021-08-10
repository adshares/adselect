<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Command;

use DateTime;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait CleanTrait
{
    protected function readFrom(InputInterface $input, OutputInterface $output): ?DateTime
    {
        $fromOption = $input->getOption('from');

        if (!$fromOption instanceof DateTime) {
            try {
                $fromDate = new DateTime($fromOption);
            } catch (Exception $exception) {
                $output->writeln(
                    sprintf(
                        '<error>Unsupported `from` format: %s. Please use supported DateTime formats.</error>',
                        $fromOption
                    )
                );

                return null;
            }
        } else {
            $fromDate = $fromOption;
        }

        return $fromDate;
    }
}
