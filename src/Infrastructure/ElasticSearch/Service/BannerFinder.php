<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Dto\FoundBanner;
use Adshares\AdSelect\Application\Dto\FoundBannersCollection;
use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Application\Service\BannerFinder as BannerFinderInterface;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\BaseQuery;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\ExpQueryBuilder;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\QueryBuilder;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\UserHistory;
use Psr\Log\LoggerInterface;
use function json_encode;

class BannerFinder implements BannerFinderInterface
{
    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;
    /** @var int */
    private $expInterval;
    /** @var int */
    private $expThreshold;

    public function __construct(Client $client, int $expInterval, int $expThreshold, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->expInterval = $expInterval;
        $this->expThreshold = $expThreshold;
    }

    public function find(QueryDto $queryDto, int $size): FoundBannersCollection
    {
        $userHistory = $this->fetchUserHistory($queryDto->getUserId());
        $defined = $this->getDefinedRequireKeywords();
        $second = date('s');
        $query = new BaseQuery($queryDto, $defined);

        $params = [
            'index' => CampaignIndex::INDEX,
            'size' => $size,
            'body' => [
                '_source' => false,
            ],
        ];

        $exp = false;
        if ($second % $this->expInterval === 0) {
            $queryBuilder = new ExpQueryBuilder($query, $this->expThreshold);
            $exp = true;
        } else {
            $queryBuilder = new QueryBuilder($query, $userHistory);
        }

        $params['body']['query'] = $queryBuilder->build();
        $this->logger->debug(sprintf('[BANNER FINDER] sending a query: %s', json_encode($params)));
        $response = $this->client->search($params);
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

        if ($exp) {
            return $collection->random();
        }

        return $collection->limit($size);
    }

    private function fetchUserHistory(string $userId): array
    {
        $params = [
            'index' => UserHistoryIndex::INDEX,
            'body' =>  UserHistory::build($userId),
        ];

        $seen = [];

        $response = $this->client->search($params);

        foreach ($response['hits']['hits'] as $hit) {
            if (!isset($seen[$hit['fields']['campaign_id'][0]])) {
                $seen[$hit['fields']['campaign_id'][0]] = 0;
            }

            $seen[$hit['fields']['campaign_id'][0]]++;
        }

        return $seen;
    }

    private function getDefinedRequireKeywords(): array
    {
        $params = ['index' => CampaignIndex::INDEX];
        $response = $this->client->getMapping($params);

        $required = [];
        foreach ($response['campaigns']['mappings']['properties'] as $key => $def) {
            if (preg_match('/^filters:require:(.+)/', $key, $match)) {
                $required[] = $match[1];
            }
        }

        return $required;
    }
}
