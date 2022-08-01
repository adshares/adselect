<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Id;
use App\Domain\ValueObject\Size;

final class Banner
{
    private Id $campaignId;
    private Id $bannerId;
    /** @var array|Size[] */
    private array $sizes;
    private array $keywords;

    public function __construct(Id $campaignId, Id $bannerId, array $sizes, array $keywords = [])
    {
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->sizes = $sizes;
        $this->keywords = $keywords;
    }

    public function getBannerId(): string
    {
        return $this->bannerId->toString();
    }

    public function getCampaignId(): string
    {
        return $this->campaignId->toString();
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getSizes(): array
    {
        return $this->sizes;
    }
}
