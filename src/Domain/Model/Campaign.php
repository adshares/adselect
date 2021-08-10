<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\ValueObject\Budget;
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
    /** @var Budget */
    private $budget;

    public function __construct(
        Id $campaignId,
        DateTimeInterface $timeStart,
        ?DateTimeInterface $timeEnd,
        BannerCollection $banners,
        array $keywords,
        array $filters,
        Budget $budget
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
        $this->budget = $budget;
    }

    public function getSourceAddress(): string
    {
        return $this->keywords['adshares_address'];
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

    public function getBudget(): int
    {
        return $this->budget->getBudget();
    }

    public function getMaxCpc(): ?int
    {
        return $this->budget->getMaxCpc();
    }

    public function getMaxCpm(): ?int
    {
        return $this->budget->getMaxCpm();
    }
}
