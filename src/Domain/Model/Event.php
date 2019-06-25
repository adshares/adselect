<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\ValueObject\EventType;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\DateTimeInterface;

final class Event
{
    /** @var Id */
    private $caseId;
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
    private $time;
    /** @var float */
    private $paidAmount;
    /** @var EventType */
    private $type;
    /** @var int */
    private $id;
    /** @var int */
    private $paymentId;
    /** @var Id */
    private $trackingId;

    public function __construct(
        int $id,
        Id $caseId,
        Id $publisherId,
        Id $userId,
        Id $trackingId,
        Id $zoneId,
        Id $campaignId,
        Id $bannerId,
        array $keywords,
        DateTimeInterface $time,
        EventType $type,
        float $paidAmount = 0,
        int $paymentId = null
    ) {
        $this->id = $id;
        $this->caseId = $caseId;
        $this->publisherId = $publisherId;
        $this->userId = $userId;
        $this->trackingId = $trackingId;
        $this->zoneId = $zoneId;
        $this->campaignId = $campaignId;
        $this->bannerId = $bannerId;
        $this->keywords = $keywords;
        $this->time = $time;
        $this->paidAmount = $paidAmount;
        $this->type = $type;
        $this->paymentId = $paymentId;
    }

    public function flatKeywords(): array
    {
        $flatKeywords = [];
        foreach ($this->keywords as $key => $values) {
            foreach ((array)$values as $value) {
                $keyword = $key . '=' . $value;
                $flatKeywords[sha1($keyword)] = $keyword;
            }
        }

        asort($flatKeywords);

        return $flatKeywords;
    }

    public function getDayDate(): string
    {
        return $this->time->format('Y-m-d');
    }

    public function getCaseId(): string
    {
        return $this->caseId->toString();
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
        return $this->time->format('Y-m-d H:i:s');
    }

    public function getPaidAmount(): ?float
    {
        return $this->paidAmount;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getType(): string
    {
        return $this->type->toString();
    }

    public function isView(): bool
    {
        return $this->type->isView();
    }

    public function isClick(): bool
    {
        return $this->type->isClick();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'case_id' => $this->caseId->toString(),
            'publisher_id' => $this->publisherId->toString(),
            'user_id' => $this->userId->toString(),
            'tracking_id' => $this->trackingId->toString(),
            'zone_id' => $this->zoneId->toString(),
            'campaign_id' => $this->campaignId->toString(),
            'banner_id' => $this->bannerId->toString(),
            'keywords' => $this->keywords,
            'time' => $this->getTime(),
            'paid_amount' => $this->paidAmount,
            'payment_id' => $this->paymentId,
        ];
    }

    public function equals(Event $event): bool
    {
        return $this->caseId->equals($event->caseId);
    }
}
