<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Service;

use Adshares\AdSelect\Application\Dto\FoundEvent;

interface EventFinder
{
    public function findLastUnpaidEvent(): FoundEvent;

    public function findLastPaidEvent(): FoundEvent;

    public function findLastCase(): FoundEvent;

    public function findLastClick(): FoundEvent;

    public function findLastPayment(): FoundEvent;
}
