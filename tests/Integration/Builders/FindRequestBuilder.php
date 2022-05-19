<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration\Builders;

final class FindRequestBuilder
{
    private array $data;

    public function __construct()
    {
        $this->data = self::default();
    }

    public function excludes(array $excludes): self
    {
        $this->data['banner_filters']['exclude'] = $excludes;
        return $this;
    }

    public function mergeKeywords(array $keywords): self
    {
        $this->data['keywords'] = array_merge($this->data['keywords'], $keywords);
        return $this;
    }

    public function requires(array $requires): self
    {
        $this->data['banner_filters']['require'] = $requires;
        return $this;
    }

    public function size(string $size): self
    {
        $this->data['banner_size'] = $size;
        return $this;
    }

    public function trackingId(string $uuid): self
    {
        $this->data['tracking_id'] = $uuid;
        return $this;
    }

    public function userId(string $uuid): self
    {
        $this->data['user_id'] = $uuid;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }

    public static function default(): array
    {
        return [
            'keywords' => [
                'device:browser' => ['chrome'],
                'device:crawler' => ['false'],
                'device:os' => ['windows'],
                'device:type' => ['desktop'],
                'human_score' => [0.9],
                'page_rank' => [1],
                'site:category' => ['crypto', 'technology'],
                'site:domain' => ['adshares.net'],
                'site:inframe' => ['no'],
                'site:page' => ['http:\/\/adshares.net\/'],
                'site:quality' => ['high'],
                'user:country' => ['us'],
                'user:language' => ['en'],
            ],
            'banner_size' => '728x90',
            'publisher_id' => '10000000000000000000000000000000',
            'site_id' => '20000000000000000000000000000000',
            'zone_id' => '30000000000000000000000000000000',
            'zone_options' => [],
            'request_id' => 0,
            'user_id' => Uuid::v4(),
            'tracking_id' => Uuid::v4(),
            'banner_filters' => [
                'require' => [
                    'test_classifier:classified' => ['1'],
                ],
                'exclude' => [],
            ],
        ];
    }
}
