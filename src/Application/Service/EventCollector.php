<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Service;

use Adshares\AdSelect\Domain\Model\EventCollection;

interface EventCollector
{
    public function collect(EventCollection $events): void;

    public function collectPaidEvents(EventCollection $events): void;
}
