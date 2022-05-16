<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration\Builders;

final class BannerBuilder
{
    private array $data;

    public function __construct()
    {
        $this->data = self::default();
    }

    public function id(string $uuid = ''): self
    {
        $this->data['banner_id'] = $uuid ?: Uuid::v4();
        return $this;
    }

    public function size(string $size): self
    {
        $this->data['banner_size'] = $size;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }

    public static function default(): array
    {
        return [
            'banner_id' => Uuid::v4(),
            'banner_size' => '728x90',
            'keywords' => [
                'type' => ['image'],
                'mime' => ['image/png'],
                'test_classifier:category' => [
                    'crypto',
                    'gambling',
                ],
                'test_classifier:classified' => ['1'],
            ],
        ];
    }
}
