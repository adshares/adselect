<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\AdserverIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Elasticsearch\Client as BaseClient;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\UnexpectedValueException;
use Psr\Log\LoggerInterface;

class Client
{
    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;
    /** @var string|null */
    private $namespace;

    public function __construct(array $hosts, LoggerInterface $logger)
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        $this->logger = $logger;

        $namespace = getenv('ES_NAMESPACE');

        $this->namespace = $namespace ? $namespace . '_' : '';
    }

    private function addNamespace(string $indexName): string
    {
        return $this->namespace . $indexName;
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
                if ($this->client->indices()->exists(['index' => $this->addNamespace($indexName)])) {
                    $this->client->indices()->delete(['index' => $this->addNamespace($indexName)]);
                }

                $this->createIndex($indexName);
            }
        }
    }

    private function findMappingsForIndex(string $indexName): array
    {
        if ($indexName === CampaignIndex::INDEX) {
            return CampaignIndex::mappings();
        }

        if ($indexName === BannerIndex::INDEX) {
            return BannerIndex::mappings();
        }

        if ($indexName === EventIndex::INDEX) {
            return EventIndex::mappings();
        }

        if ($indexName === AdserverIndex::INDEX) {
            return AdserverIndex::mappings();
        }


        throw new ElasticSearchRuntime(sprintf('Given index (%s) does not exists', $indexName));
    }

    public function createIndexes(bool $force = false): void
    {
        $this->createIndex(BannerIndex::INDEX, $force);
        $this->createIndex(EventIndex::INDEX, $force);
        $this->createIndex(AdserverIndex::INDEX, $force);
    }

    public function indexExists(string $indexName): bool
    {
        return $this->client->indices()->exists(['index' => $indexName]);
    }

    public function refreshIndex(string $indexName): array
    {
        return $this->client->indices()->refresh(['index' => $indexName]);
    }

    public function bulk(array $mapped, string $type): array
    {
        try {
            $response = $this->client->bulk(['body' => $mapped]);

            if ($response['errors']) {
                $errors = array_map(
                    static function ($item) {
                        return $item;
                    },
                    $response['items']
                );

                $this->logger->notice(
                    sprintf(
                        '[%s] Update data to ES failed. ES ERROR: %s QUERY: %s',
                        $type,
                        json_encode($errors),
                        json_encode($mapped)
                    )
                );

                return $response;
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

    public function delete(array $query, string $indexName): void
    {
        $params = [
            'index' => $indexName,
            'body'  => [
                'query' => $query,
            ],
        ];

        try {
            $result = $this->client->deleteByQuery($params);
            $this->logger->debug(
                sprintf(
                    '%s documents has been removed from index %s',
                    $result['deleted'],
                    $indexName
                )
            );
        } catch (BadRequest400Exception $exception) {
            $this->logger->error(
                sprintf(
                    'Documents from index %s could not be removed (%s)',
                    $indexName,
                    $exception->getMessage()
                )
            );
        }
    }
}
