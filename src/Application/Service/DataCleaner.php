<?php

declare(strict_types=1);

namespace App\Application\Service;

use DateTime;

interface DataCleaner
{
    public function cleanUserHistory(DateTime $date): void;

    public function cleanEvents(DateTime $date): void;
}
