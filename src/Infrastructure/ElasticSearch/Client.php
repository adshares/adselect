<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Elasticsearch\Client as BaseClient;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\UnexpectedValueException;
use function current;
use function implode;
use function sprintf;

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

    public function createEventIndex(bool $force = false)
    {
        try {
            $this->client->indices()->create(EventIndex::mappings());
        } catch (BadRequest400Exception $exception) {
            if ($force) {
                $this->client->indices()->delete(['index' => EventIndex::INDEX]);

                return;
            }

            throw new ElasticSearchRuntime($exception->getMessage());
        }
    }

    public function createCampaignIndex(bool $force = false): void
    {
        try {
            $this->client->indices()->create(CampaignIndex::mappings());
        } catch (BadRequest400Exception $exception) {
            if ($force) {
                $this->client->indices()->delete(['index' => CampaignIndex::INDEX]);

                return;
            }

            throw new ElasticSearchRuntime($exception->getMessage());
        }
    }

    public function createIndexes(bool $force = false): void
    {
        $this->createCampaignIndex($force);
        $this->createEventIndex($force);
    }

    public function campaignIndexExists(): bool
    {
        return $this->client->indices()->exists(['index' => CampaignIndex::INDEX]);
    }

    public function eventIndexExists(): bool
    {
        return $this->client->indices()->exists(['index' => EventIndex::INDEX]);
    }

    public function bulk(array $mapped, string $type): void
    {
        try {
            $this->client->bulk(['body' => $mapped]);
        } catch (UnexpectedValueException $exception) {
            $ids = [];
            foreach ($mapped as $item) {
                $current = current($item);

                if (isset($current['_id'])) {
                    $ids[] = $current['_id'];
                }
            }

            $message = sprintf(
                '[%s] Update data to ES failed. Problem with ids: %s',
                $type,
                implode(', ', $ids)
            );

            throw new ElasticSearchRuntime($message, 0, $exception);
        }
    }
}
