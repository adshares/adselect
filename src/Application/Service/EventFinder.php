<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Dto\FoundEvent;

interface EventFinder
{
    public function findLastUnpaidEvent(): FoundEvent;

    public function findLastPaidEvent(): FoundEvent;

    public function findLastCase(): FoundEvent;

    public function findLastClick(): FoundEvent;

    public function findLastPayment(): FoundEvent;
}
