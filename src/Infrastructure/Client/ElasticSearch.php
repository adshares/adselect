<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\Client;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;

class ElasticSearch
{
    /** @var Client */
    private $client;

    public function __construct(array $hosts)
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function createIndexes(bool $force = false): void
    {
        try {
            $this->client->indices()->create(BannerIndex::mappings());
        } catch (BadRequest400Exception $exception) {
            if ($force) {
                $this->client->indices()->delete(['index' => BannerIndex::INDEX]);
                $this->createIndexes();

                return;
            }

            throw new ElasticSearchRuntime($exception->getMessage());
        }
    }
}
