<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Service;

use App\Application\Service\DataCleaner as BaseDataCleaner;
use App\Infrastructure\ElasticSearch\Client;
use App\Infrastructure\ElasticSearch\Mapping\EventIndex;
use App\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use App\Infrastructure\ElasticSearch\QueryBuilder\CleanQuery;
use DateTime;

class DataCleaner implements BaseDataCleaner
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function cleanUserHistory(DateTime $date): void
    {
        $this->client->delete(CleanQuery::build($date), UserHistoryIndex::name());
    }

    public function cleanEvents(DateTime $date): void
    {
        $this->client->delete(CleanQuery::build($date), EventIndex::name());
    }
}
