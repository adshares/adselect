<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Dto;

final class FoundBanner
{
    private string $campaignId;
    private string $bannerId;
    private string $size;
    private ?float $rpm;

    public function __construct(string $campaignId, string $bannerId, string $size, ?float $rpm)
    {
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->size = $size;
        $this->rpm = $rpm;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function getBannerId(): string
    {
        return $this->bannerId;
    }

    public function getRpm(): ?float
    {
        return $this->rpm;
    }

    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'banner_id' => $this->bannerId,
            'size' => $this->size,
            'rpm' => $this->rpm,
        ];
    }
}
