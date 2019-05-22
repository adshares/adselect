<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIntersectIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use Elasticsearch\Client as BaseClient;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\UnexpectedValueException;
use Psr\Log\LoggerInterface;
use function current;
use function implode;
use function json_encode;
use function sprintf;

class Client
{
    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(array $hosts, LoggerInterface $logger)
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        $this->logger = $logger;
    }

    public function getClient(): BaseClient
    {
        return $this->client;
    }

    public function createIndex(string $indexName, bool $force = false): void
    {
        try {
            $this->client->indices()->create($this->findMappingsForIndex($indexName));
        } catch (BadRequest400Exception $exception) {
            if ($force) {
                if ($this->client->indices()->exists(['index' => $indexName])) {
                    $this->client->indices()->delete(['index' => $indexName]);
                }
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

        if ($indexName === KeywordIntersectIndex::INDEX) {
            return KeywordIntersectIndex::mappings();
        }

        throw new ElasticSearchRuntime(sprintf('Given index (%s) does not exists', $indexName));
    }

    public function createIndexes(bool $force = false): void
    {
        $this->createIndex(CampaignIndex::INDEX, $force);
        $this->createIndex(EventIndex::INDEX, $force);
        $this->createIndex(UserHistoryIndex::INDEX, $force);
        $this->createIndex(KeywordIndex::INDEX, $force);
        $this->createIndex(KeywordIntersectIndex::INDEX, $force);
    }

    public function indexExists(string $indexName): bool
    {
        return $this->client->indices()->exists(['index' => $indexName]);
    }

    public function bulk(array $mapped, string $type): array
    {
        try {
            $response =  $this->client->bulk(['body' => $mapped]);

            if ($response['errors'] === true) {
                $errors = json_encode(array_map(
                    static function ($item) {
                        return $item;
                    },
                    $response['items']
                ));

                $this->logger->notice(sprintf('[%s] Update data to ES failed. ES ERROR: %s', $type, $errors));

                return [];
            }

            return $response;
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

    public function search(array $params): array
    {
        return $this->client->search($params);
    }

    public function getMapping(array $params): array
    {
        return $this->client->indices()->getMapping($params);
    }
}
