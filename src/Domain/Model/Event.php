<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\Id;

final class Event
{
    /** @var Id */
    private $eventId;
    /** @var Id */
    private $publisherId;
    /** @var Id */
    private $userId;
    /** @var Id */
    private $zoneId;
    /** @var Id */
    private $campaignId;
    /** @var Id */
    private $bannerId;
    /** @var array */
    private $keywords;

    public function __construct(
        Id $eventId,
        Id $publisherId,
        Id $userId,
        Id $zoneId,
        Id $campaignId,
        Id $bannerId,
        array $keywords
    ) {

        $this->eventId = $eventId;
        $this->publisherId = $publisherId;
        $this->userId = $userId;
        $this->zoneId = $zoneId;
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->keywords = $keywords;
    }

    public function getId(): string
    {
        return $this->eventId->toString();
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId->toString(),
            'publisher_id' => $this->publisherId->toString(),
            'user_id' => $this->userId->toString(),
            'zone_id' => $this->zoneId->toString(),
            'campaign_id' => $this->campaignId->toString(),
            'banner_id' => $this->bannerId->toString(),
            'keywords' => $this->keywords,
        ];
    }
}
