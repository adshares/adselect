<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch;

use App\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use App\Infrastructure\ElasticSearch\Mapping\AdserverIndex;
use App\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use App\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use App\Infrastructure\ElasticSearch\Mapping\EventIndex;
use App\Infrastructure\ElasticSearch\Mapping\ExperimentPaymentIndex;
use Elasticsearch\Client as BaseClient;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\UnexpectedValueException;
use Psr\Log\LoggerInterface;

class Client
{
    private BaseClient $client;
    private LoggerInterface $logger;
    private string $namespace;

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
        switch ($indexName) {
            case CampaignIndex::INDEX:
                $mappings = CampaignIndex::mappings();
                break;
            case BannerIndex::INDEX:
                $mappings = BannerIndex::mappings();
                break;
            case EventIndex::INDEX:
                $mappings = EventIndex::mappings();
                break;
            case AdserverIndex::INDEX:
                $mappings = AdserverIndex::mappings();
                break;
            case ExperimentPaymentIndex::INDEX:
                $mappings = ExperimentPaymentIndex::mappings();
                break;
            default:
                throw new ElasticSearchRuntime(sprintf('Given index (%s) does not exists', $indexName));
        }
        return $mappings;
    }

    public function createIndexes(bool $force = false): void
    {
        $this->createIndex(BannerIndex::INDEX, $force);
        $this->createIndex(EventIndex::INDEX, $force);
        $this->createIndex(AdserverIndex::INDEX, $force);
        $this->createIndex(ExperimentPaymentIndex::INDEX, $force);
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
            'body' => [
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
