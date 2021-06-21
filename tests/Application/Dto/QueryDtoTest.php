<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Application\Dto;

use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use PHPUnit\Framework\TestCase;

final class QueryDtoTest extends TestCase
{
    /**
     * @dataProvider providerForArray
     */
    public function testQueryDtoFromArray(array $input, bool $expectedException = false): void
    {
        if ($expectedException) {
            $this->expectException(ValidationDtoException::class);
        }

        $query = QueryDto::fromArray($input);
        $this->assertEquals($input['user_id'], $query->getUserId());
        $this->assertEquals($input['keywords'], $query->getKeywords());

        if (isset($input['banner_filters']['require'])) {
            $this->assertEquals($input['banner_filters']['require'], $query->getRequireFilters());
        }

        if (isset($input['banner_filters']['exclude'])) {
            $this->assertEquals($input['banner_filters']['exclude'], $query->getExcludeFilters());
        }
    }

    public function providerForArray(): array
    {
        return [
            [
                [
                    'publisher_id' => '00000000000000000000000000000001',
                    'site_id' => '00000000000000000000000000000001',
                    'zone_id' => '00000000000000000000000000000001',
                    'user_id' => '00000000000000000000000000000002',
                    'tracking_id' => '00000000000000000000000000000002',
                    'banner_size' => '620x300',
                    'keywords' => [
                        'keyword1',
                        'keyword2',
                    ],
                    'banner_filters' => [
                        'require' => [
                            'one' => 1,
                        ],
                        'exclude' => [
                            'two' => 2,
                        ],
                    ],
                ],
                false
            ],
            [
                [
                    'publisher_id' => '00000000000000000000000000000001',
                    'site_id' => '00000000000000000000000000000001',
                    'zone_id' => '00000000000000000000000000000001',
                    'user_id' => '00000000000000000000000000000002',
                    'tracking_id' => '00000000000000000000000000000002',
                    'banner_size' => '620x300',
                    'keywords' => [],
                    'banner_filters' => [],
                ],
                false
            ],
            [
                [
                    'publisher_id' => '000000001',
                    'site_id' => '00000000000000000000000000000001',
                    'zone_id' => '00000000000000000000000000000001',
                    'user_id' => '00000000000000000000000000000002',
                    'tracking_id' => '00000000000000000000000000000002',
                    'banner_size' => '620x300',
                    'keywords' => [
                        'keyword1',
                        'keyword2',
                    ],
                    'banner_filters' => [
                        'require' => [
                            'one' => 1,
                        ],
                        'exclude' => [
                            'two' => 2,
                        ],
                    ],
                ],
                true
            ],
            [
                [
                    'banner_size' => '620x300',
                    'keywords' => [
                        'keyword1',
                        'keyword2',
                    ],
                    'banner_filters' => [
                        'require' => [
                            'one' => 1,
                        ],
                        'exclude' => [
                            'two' => 2,
                        ],
                    ],
                ],
                true
            ],
            [
                [
                    'publisher_id' => '00000000000000000000000000000001',
                    'user_id' => '00000000000000000000000000000002',
                    'tracking_id' => '00000000000000000000000000000002',
                    'banner_size' => '620300',
                    'keywords' => [
                        'keyword1',
                        'keyword2',
                    ],
                    'banner_filters' => [
                        'require' => [
                            'one' => 1,
                        ],
                        'exclude' => [
                            'two' => 2,
                        ],
                    ],
                ],
                true
            ],
        ];
    }
}
