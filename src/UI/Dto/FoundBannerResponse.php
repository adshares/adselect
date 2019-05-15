<?php

declare(strict_types = 1);

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
            $data[] = [
                'campaign_id' => $banner->getCampaignId(),
                'banner_id' => $banner->getBannerId(),
            ];
        }

        return $data;
    }
}
