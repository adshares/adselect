<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Dto\FoundExperimentPayment;
use App\Application\Exception\ExperimentPaymentNotFound;

interface ExperimentPaymentFinder
{
    /**
     * @return FoundExperimentPayment
     * @throws ExperimentPaymentNotFound
     */
    public function findLastPayment(): FoundExperimentPayment;
}
