<?php
/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */
declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class BannerIndex implements Index
{
    public const INDEX = 'banners';

    public const SETTINGS = [
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
        'analysis' => [
            'filter' => [
                'shingle' => [
                    'type' => 'shingle'
                ]
            ],
            'char_filter' => [
                'pre_negs' => [
                    'type' => 'pattern_replace',
                    'pattern' => '(\\w+)\\s+((?i:never|no|nothing|nowhere|noone|none|not|havent|hasnt|hadnt|cant|couldnt|shouldnt|wont|wouldnt|dont|doesnt|didnt|isnt|arent|aint))\\b',
                    'replacement' => '~$1 $2'
                ],
                'post_negs' => [
                    'type' => 'pattern_replace',
                    'pattern' => '\\b((?i:never|no|nothing|nowhere|noone|none|not|havent|hasnt|hadnt|cant|couldnt|shouldnt|wont|wouldnt|dont|doesnt|didnt|isnt|arent|aint))\\s+(\\w+)',
                    'replacement' => '$1 ~$2'
                ]
            ],
            'analyzer' => [
                'reuters' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => ['lowercase', 'stop', 'kstem']
                ]
            ]
        ]
    ];

    public const MAPPINGS = [
        'my_type' => [
            'properties' => [
                'my_field' => [
                    'type' => 'keyword',
                ],
            ],
        ],
    ];

    public static function mappings(): array
    {
        return [
            'index' => self::INDEX,
            'body' => [
                'settings' => self::SETTINGS,
                'mappings' => self::MAPPINGS,
            ],
        ];
    }
}
