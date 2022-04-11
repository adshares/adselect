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
            'campaign_id' => '0123456789abcdef0123456789abcdef',
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
            'max_cpc' => 10000000001,
            'max_cpm' => 10000000002,
            'budget' => 93555000000,
        ];
    }
}
