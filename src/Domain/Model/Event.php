<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\DateTimeInterface;
use function substr;

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
    /** @var DateTimeInterface */
    private $date;
    /** @var float|null */
    private $paidAmount;

    public function __construct(
        Id $eventId,
        Id $publisherId,
        Id $userId,
        Id $zoneId,
        Id $campaignId,
        Id $bannerId,
        array $keywords,
        DateTimeInterface $date,
        ?float $paidAmount = null
    ) {
        $this->eventId = $this->getCaseIdFromEvent($eventId);
        $this->publisherId = $publisherId;
        $this->userId = $userId;
        $this->zoneId = $zoneId;
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->keywords = $keywords;
        $this->date = $date;
        $this->paidAmount = $paidAmount;
    }

    private function getCaseIdFromEvent(Id $eventId): Id
    {
        $id = substr($eventId->toString(), 0, -2).'00';

        return new Id($id);
    }

    public function getId(): string
    {
        return $this->eventId->toString();
    }

    public function getUserId(): string
    {
        return $this->userId->toString();
    }

    public function getCampaignId(): string
    {
        return $this->campaignId->toString();
    }

    public function getBannerId(): string
    {
        return $this->bannerId->toString();
    }

    public function getDate(): string
    {
        return $this->date->format('Y-m-d H:i:s');
    }

    public function getPaidAmount(): ?float
    {
        return $this->paidAmount;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
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
            'date' => $this->getDate(),
            'paid_amount' => $this->paidAmount,
        ];
    }
}
