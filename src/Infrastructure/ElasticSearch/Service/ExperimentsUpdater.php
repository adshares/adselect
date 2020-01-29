<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\AdserverMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\BannerMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\AdserverIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use DateTime;

class ExperimentsUpdater
{
    /** @var Client */
    private $client;

    private $updateCache = [];
    private $bulkLimit;

    public function __construct(Client $client, int $bulkLimit = 100)
    {
        $this->client = $client;
        $this->bulkLimit = 2 * $bulkLimit;
    }

    public function recalculateExperiments(\DateTimeInterface $from): void
    {

    }
}
