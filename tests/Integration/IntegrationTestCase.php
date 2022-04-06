<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IntegrationTestCase extends WebTestCase
{
    private ?Client $esClient = null;

    public function getEsClient(): Client
    {
        if ($this->esClient === null) {
            $this->esClient = ClientBuilder::create()->build();
        }
        return $this->esClient;
    }

    /**
     * @after
     */
    public function deleteIndices(): void
    {
        $indices = $this->getEsClient()->cat()->indices(['index' => '*']);
        foreach ($indices as $indexData) {
            if ($this->isSystemIndex($indexData['index'])) {
                continue;
            }
            $this->getEsClient()->indices()->delete(['index' => $indexData['index']]);
        }
    }

    public function indexExists(string $indexName): bool
    {
        return $this->getEsClient()->indices()->exists(['index' => $indexName]);
    }

    public function documentsInIndex(string $indexName): array
    {
        return $this->getEsClient()->search(['index' => $indexName])['hits']['hits'] ?? [];
    }

    private function isSystemIndex(string $indexName): bool
    {
        return str_starts_with($indexName, '.');
    }
}
