<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\Uuid;

final class Banner
{
    /** @var Uuid */
    private $campaignId;
    /** @var Uuid */
    private $bannerId;
    /** @var string */
    private $size;
    /** @var array */
    private $keywords;

    public function __construct(Uuid $campaignId, Uuid $bannerId, string $size, array $keywords = [])
    {
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->size = $size;
        $this->keywords = $keywords;
    }
}
