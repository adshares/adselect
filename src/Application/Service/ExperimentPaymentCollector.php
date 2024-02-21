<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Model\ExperimentPaymentCollection;

interface ExperimentPaymentCollector
{
    public function collectPayments(ExperimentPaymentCollection $payments): void;
}
