<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Dto\FoundBannersCollection;
use App\Application\Dto\QueryDto;

interface BannerFinder
{
    public function find(QueryDto $queryDto, int $resultCount = 1): FoundBannersCollection;
}
