<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Domain\ValueObject\Size;

final class Banner
{
    /** @var Id */
    private $campaignId;
    /** @var Id */
    private $bannerId;
    /** @var Size */
    private $size;
    /** @var array */
    private $keywords;

    public function __construct(Id $campaignId, Id $bannerId, Size $size, array $keywords = [])
    {
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->size = $size;
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

    public function getSize(): Size
    {
        return $this->size;
    }
}
