<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
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

    private function createIndex(string $indexName, bool $force = false): void
    {
        try {
            $this->client->indices()->create($this->findMappingsForIndex($indexName));
        } catch (BadRequest400Exception $exception) {
            if ($force) {
                $this->client->indices()->delete(['index' => $indexName]);
                $this->createIndex($indexName);

                return;
            }

            throw new ElasticSearchRuntime($exception->getMessage());
        }
    }

    private function findMappingsForIndex(string $indexName): array
    {
        if ($indexName === CampaignIndex::INDEX) {
            return CampaignIndex::mappings();
        }

        if ($indexName === EventIndex::INDEX) {
            return EventIndex::mappings();
        }

        if ($indexName === UserHistoryIndex::INDEX) {
            return UserHistoryIndex::mappings();
        }

        if ($indexName === KeywordIndex::INDEX) {
            return KeywordIndex::mappings();
        }

        throw new ElasticSearchRuntime(sprintf('Given index (%s) does not exists', $indexName));
    }

    public function createCampaignIndex(bool $force = false): void
    {
        $this->createIndex(CampaignIndex::INDEX, $force);
    }

    public function createEventIndex(bool $force = false): void
    {
        $this->createIndex(EventIndex::INDEX, $force);
    }

    public function createUserHistory(bool $force = false): void
    {
        $this->createIndex(UserHistoryIndex::INDEX, $force);
    }

    public function createKeywordIndex(bool $force = false): void
    {
        $this->createIndex(KeywordIndex::INDEX, $force);
    }


    public function createIndexes(bool $force = false): void
    {
        $this->createCampaignIndex($force);
        $this->createEventIndex($force);
        $this->createUserHistory($force);
        $this->createKeywordIndex($force);
    }

    public function campaignIndexExists(): bool
    {
        return $this->client->indices()->exists(['index' => CampaignIndex::INDEX]);
    }

    public function eventIndexExists(): bool
    {
        return $this->client->indices()->exists(['index' => EventIndex::INDEX]);
    }

    public function userHistoryIndexExists(): bool
    {
        return $this->client->indices()->exists(['index' => UserHistoryIndex::INDEX]);
    }

    public function keywordIndexExists(): bool
    {
        return $this->client->indices()->exists(['index' => KeywordIndex::INDEX]);
    }

    public function bulk(array $mapped, string $type): array
    {
        try {
            return $this->client->bulk(['body' => $mapped]);
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
