<?php

declare(strict_types=1);

namespace App\Tests\Integration\Services;

use App\Infrastructure\ElasticSearch\Client;

class EsClientWrapper extends Client
{
    private static int $seed = 1;

    public function search(array $params): array
    {
        if (self::isRandomnessDisabled()) {
            array_walk_recursive(
                $params,
                function (&$item) {
                    if (is_string($item) && false !== strpos($item, 'Math.random()')) {
                        $item = str_replace('Math.random()', self::getPseudoRandomValue(), $item);
                    }
                }
            );
        }
        return parent::search($params);
    }

    private static function getPseudoRandomValue(): string
    {
        srand(self::$seed++);
        return strval(rand() / getrandmax());
    }

    private static function isRandomnessDisabled(): bool
    {
        return ($_ENV['DISABLE_RANDOMNESS'] ?? 0) === 1;
    }
}
