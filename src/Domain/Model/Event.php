<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\DateTimeInterface;

final class Event
{
    /** @var int */
    private $id;
    /** @var DateTimeInterface */
    private $createdAt;
    /** @var Id */
    private $publisherId;
    /** @var Id */
    private $siteId;
    /** @var Id */
    private $zoneId;
    /** @var Id */
    private $campaignId;
    /** @var Id */
    private $bannerId;
    /** @var Id */
    private $impressionId;
    /** @var Id */
    private $trackingId;
    /** @var Id */
    private $userId;
    /** @var array */
    private $keywords;

    /** @var int */
    private $clickId;
    /** @var DateTimeInterface */
    private $clickTime;

    /** @var float */
    private $paidAmount;
    /** @var int */
    private $lastPaymentId;
    /** @var DateTimeInterface */
    private $lastPaymentTime;

    public function __construct(
        int $id,
        DateTimeInterface $createdAt,
        Id $publisherId,
        Id $siteId,
        Id $zoneId,
        Id $campaignId,
        Id $bannerId,
        Id $impressionId,
        Id $trackingId,
        Id $userId,
        array $keywords
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->publisherId = $publisherId;
        $this->siteId = $siteId;
        $this->zoneId = $zoneId;
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->impressionId = $impressionId;
        $this->trackingId = $trackingId;
        $this->userId = $userId;
        $this->keywords = [];

        $this->clickId = null;
        $this->clickTime = null;

        $this->paidAmount = 0;
        $this->lastPaymentId = null;
        $this->lastPaymentTime = null;
        $this->keywords = $keywords;
    }

    public function flatKeywords(): array
    {
        $ret = [];

        foreach ($this->keywords as $key => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                $ret[] = $key . '=' . $value;
            }
        }

        return $ret;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDayDate(): string
    {
        return $this->createdAt->format('Y-m-d');
    }

    public function getImpressionId(): string
    {
        return $this->impressionId->toString();
    }

    public function getUserId(): string
    {
        return $this->userId->toString();
    }

    public function getTrackingId(): string
    {
        return $this->trackingId->toString();
    }

    public function getCampaignId(): string
    {
        return $this->campaignId->toString();
    }

    public function getBannerId(): string
    {
        return $this->bannerId->toString();
    }

    public function getTime(): string
    {
        return $this->createdAt->format('Y-m-d H:i:s');
    }

    public function getPaidAmount(): ?float
    {
        return $this->paidAmount;
    }

    public function getPaymentId(): ?int
    {
        return $this->lastPaymentId;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'time' => $this->getTime(),
            'publisher_id' => $this->publisherId->toString(),
            'site_id' => $this->siteId->toString(),
            'zone_id' => $this->zoneId->toString(),
            'user_id' => $this->userId->toString(),
            'tracking_id' => $this->trackingId->toString(),
            'impression_id' => $this->impressionId->toString(),
            'campaign_id' => $this->campaignId->toString(),
            'banner_id' => $this->bannerId->toString(),
            'paid_amount' => $this->paidAmount,
            'last_payment_id' => $this->lastPaymentId,
            'last_payment_time' => $this->lastPaymentTime,
            'click_id' => $this->clickId,
            'click_time' => $this->clickTime,
        ];
    }

    public function equals(Event $event): bool
    {
        return $this->id === $event->id;
    }
}
