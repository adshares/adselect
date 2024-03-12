<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Model\BoostPaymentCollection;

interface BoostPaymentCollector
{
    public function collectPayments(BoostPaymentCollection $payments): void;
}
