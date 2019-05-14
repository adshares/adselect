<?php

namespace Adshares\AdSelect\Application\Service;

use Adshares\AdSelect\Application\Dto\QueryDto;

interface BannerFinder
{
    public function find(QueryDto $queryDto): array;
}
