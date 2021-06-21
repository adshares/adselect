<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Service\DataCleaner as BaseDataCleaner;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\CleanQuery;
use DateTime;

class DataCleaner implements BaseDataCleaner
{
    /** @var Client */
    private $client;

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
