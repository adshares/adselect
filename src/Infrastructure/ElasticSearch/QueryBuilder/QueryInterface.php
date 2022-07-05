<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\QueryBuilder;

interface QueryInterface
{
    public function build(): array;
}
