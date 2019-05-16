<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Dto\FoundBanner;
use Adshares\AdSelect\Application\Dto\FoundBannersCollection;
use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Application\Service\BannerFinder as BannerFinderInterface;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\QueryBuilder;

class BannerFinder implements BannerFinderInterface
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function find(QueryDto $queryDto): FoundBannersCollection
    {
        $defined = $this->getDefinedRequireKeywords();
        $queryBuilder = new QueryBuilder($queryDto, $defined);

        $params = [
            'index' => CampaignIndex::INDEX,
            'body' => [
                '_source' => false,
                'query' => $queryBuilder->build()
            ]
        ];

        $response = $this->client->getClient()->search($params);
        $collection = new FoundBannersCollection();

        if ($response['hits']['total']['value'] === 0) {
            return $collection;
        }

        foreach ($response['hits']['hits'] as $hit) {
            if (!isset($hit['inner_hits']['banners']['hits']['hits'])) {
                return null;
            }

            foreach ($hit['inner_hits']['banners']['hits']['hits'] as $bannerHit) {
                $collection->add(new FoundBanner(
                    $hit['_id'],
                    $bannerHit['fields']['banners.id'][0],
                    $bannerHit['fields']['banners.size'][0]
                ));
            }
        }

        return $collection;
    }

    private function getDefinedRequireKeywords(): array
    {
        $params = ['index' => CampaignIndex::INDEX];
        $response = $this->client->getClient()->indices()->getMapping($params);

        $required = [];
        foreach ($response['campaigns']['mappings']['properties'] as $key => $def) {
            if (preg_match('/^filters:require:(.+)/', $key, $match)) {
                $required[] = $match[1];
            }
        }

        return $required;
    }
}