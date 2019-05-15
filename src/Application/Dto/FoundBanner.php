<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

final class FoundBanner
{
    /** @var string */
    private $campaignId;
    /** @var string */
    private $bannerId;
    /** @var string */
    private $size;

    public function __construct(string $campaignId, string $bannerId, string $size)
    {
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->size = $size;
    }

    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'banner_id' => $this->bannerId,
        ];
    }
}
