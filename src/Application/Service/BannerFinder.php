<?php

namespace Adshares\AdSelect\Application\Service;

use Adshares\AdSelect\Application\Dto\BannerFinderDto;

interface BannerFinder
{
    public function find(BannerFinderDto $bannerFinderDto): array;
}
