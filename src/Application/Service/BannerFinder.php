<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Service;

use Adshares\AdSelect\Application\Dto\FoundBannersCollection;
use Adshares\AdSelect\Application\Dto\QueryDto;

interface BannerFinder
{
    public function find(QueryDto $queryDto, int $size): FoundBannersCollection;
}
