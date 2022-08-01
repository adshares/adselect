<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Model\EventCollection;

interface EventCollector
{
    public function collectCases(EventCollection $events): void;

    public function collectClicks(EventCollection $events): void;

    public function collectPayments(EventCollection $events): void;
}
