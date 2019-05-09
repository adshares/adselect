<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\Adselect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\DateTimeInterface;

final class Campaign
{
    /** @var Id */
    private $campaignId;
    /** @var DateTimeInterface */
    private $timeStart;
    /** @var DateTimeInterface|null */
    private $timeEnd;
    /** @var BannerCollection */
    private $banners;
    /** @var array */
    private $keywords;
    /** @var array */
    private $filters;

    public function __construct(
        Id $campaignId,
        DateTimeInterface $timeStart,
        ?DateTimeInterface $timeEnd,
        BannerCollection $banners,
        array $keywords,
        array $filters
    ) {
        if ($timeEnd && $timeStart > $timeEnd) {
            throw new AdSelectRuntimeException(sprintf(
                'Time start (%s) must be greater than end date (%s).',
                $timeStart->toString(),
                $timeEnd->toString()
            ));
        }

        $this->campaignId = $campaignId;
        $this->timeStart = $timeStart;
        $this->timeEnd = $timeEnd;
        $this->banners = $banners;
        $this->keywords = $keywords;
        $this->filters = [
            'exclude' => $filters['exclude'] ?? [],
            'require' => $filters['require'] ?? [],
        ];
    }

    public function toArray(): array
    {
        $banners = [];

        foreach ($this->banners as $banner) {
            $banners[] = $banner->toArray();
        }

        return [
            'campaignId' => $this->campaignId,
            'timeStart' => $this->timeStart,
            'timeEnd' => $this->timeEnd,
            'keywords' => $this->keywords,
            'filters' => $this->filters,
            'banners' => $banners,
        ];
    }

    public function getId(): string
    {
        return $this->campaignId->toString();
    }

    public function getTimeStart(): int
    {
        return $this->timeStart->getTimestamp();
    }

    public function getTimeEnd(): ?int
    {
        if (!$this->timeEnd) {
            return null;
        }

        return $this->timeEnd->getTimestamp();
    }

    public function getBanners(): BannerCollection
    {
        return $this->banners;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getExcludeFilters(): array
    {
        return $this->filters['exclude'];
    }

    public function getRequireFilters(): array
    {
        return $this->filters['require'];
    }
}
