<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration\Builders;

use DateTimeImmutable;
use DateTimeInterface;

final class CampaignBuilder
{
    private array $data;

    public function __construct()
    {
        $this->data = self::default();
    }

    public function banners(array $banners): self
    {
        $this->data['banners'] = $banners;
        return $this;
    }

    public function budget(int $budget): self
    {
        $this->data['budget'] = $budget;
        return $this;
    }

    public function excludes(array $excludes): self
    {
        $this->data['filters']['exclude'] = $excludes;
        return $this;
    }

    public function id(string $uuid = ''): self
    {
        $this->data['campaign_id'] = $uuid ?: Uuid::v4();
        return $this;
    }

    public function noTimeEnd(): self
    {
        $this->data['time_end'] = null;
        return $this;
    }

    public function timeEnd(DateTimeInterface $dateTime): self
    {
        $this->data['time_end'] = $dateTime->getTimestamp();
        return $this;
    }

    public function timeStart(DateTimeInterface $dateTime): self
    {
        $this->data['time_start'] = $dateTime->getTimestamp();
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }

    public static function default(): array
    {
        return [
            'campaign_id' => Uuid::v4(),
            'time_start' => (new DateTimeImmutable())->getTimestamp(),
            'time_end' => (new DateTimeImmutable('+10 day'))->getTimestamp(),
            'banners' => [
                BannerBuilder::default(),
            ],
            'keywords' => [
                'source_host' => 'https://example.com',
                'adshares_address' => '0001:00000001:XXXX',
            ],
            'filters' => [
                'require' => [
                    'device:type' => ['desktop'],
                ],
                'exclude' => [],
            ],
            'max_cpc' => 10_000_000_001,
            'max_cpm' => 10_000_000_002,
            'budget' => 93_555_000_000,
        ];
    }
}
