<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\Id;

final class Banner
{
    /** @var Id */
    private $campaignId;
    /** @var Id */
    private $bannerId;
    /** @var string */
    private $size;
    /** @var array */
    private $keywords;

    public function __construct(Id $campaignId, Id $bannerId, string $size, array $keywords = [])
    {
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->size = $size;
        $this->keywords = $keywords;
    }

    public function toArray(): array
    {
        return [
            'bannerId' => $this->bannerId,
            'campaignId' => $this->campaignId,
            'size' => $this->size,
            'keywords' => $this->keywords,
        ];
    }
}
