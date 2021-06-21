<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Dto;

use Adshares\AdSelect\Application\Dto\FoundBanner;
use Adshares\AdSelect\Application\Dto\FoundBannersCollection;

class FoundBannerResponse
{
    /** @var FoundBannersCollection */
    private $collection;

    public function __construct(FoundBannersCollection $collection)
    {
        $this->collection = $collection;
    }

    public function toArray(): array
    {
        $data = [];

        /** @var FoundBanner $banner */
        foreach ($this->collection as $banner) {
            $data[] = $banner->toArray();
        }

        return $data;
    }
}
