<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use Elasticsearch\Client as BaseClient;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;

class Client
{
    /** @var Client */
    private $client;

    public function __construct(array $hosts)
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    public function getClient(): BaseClient
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

    public function indexesExist(): bool
    {
        return $this->client->indices()->exists(['index' => BannerIndex::INDEX]);
    }
}
