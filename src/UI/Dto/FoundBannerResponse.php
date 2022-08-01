<?php

declare(strict_types=1);

namespace App\UI\Dto;

use App\Application\Dto\FoundBanner;
use App\Application\Dto\FoundBannersCollection;

class FoundBannerResponse
{
    private FoundBannersCollection $collection;

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
