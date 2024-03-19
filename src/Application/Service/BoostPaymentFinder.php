<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Dto\FoundBoostPayment;
use App\Application\Exception\BoostPaymentNotFound;

interface BoostPaymentFinder
{
    /**
     * @return FoundBoostPayment
     * @throws BoostPaymentNotFound
     */
    public function findLastPayment(): FoundBoostPayment;
}
