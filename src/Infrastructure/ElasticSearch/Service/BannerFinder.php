<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Dto\FoundBanner;
use Adshares\AdSelect\Application\Dto\FoundBannersCollection;
use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Application\Service\BannerFinder as BannerFinderInterface;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\UserHistoryMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\BaseQuery;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\ExpQueryBuilder;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\QueryBuilder;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\UserHistory;
use DateTime;
use Psr\Log\LoggerInterface;
use function json_encode;

class BannerFinder implements BannerFinderInterface
{
    private const BANNER_SIZE_RETURNED = 1;

    private const HISTORY_APC_KEY_PREFIX = 'Adselect.UserHistory';
    private const HISTORY_ENTRY_TIME = 0;
    const HISTORY_ENTRY_CAMPAIGN_ID = 1;
    private const HISTORY_MAXAGE = 3600;
    private const HISTORY_MAXENTRIES = 50;

    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;
    /** @var int */
    private $expInterval;
    /** @var int */
    private $expThreshold;
    /** @var int */
    private $scoreThreshold;

    public function __construct(
        Client $client,
        int $expInterval,
        int $expThreshold,
        int $scoreThreshold,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->expInterval = $expInterval;
        $this->expThreshold = $expThreshold;
        $this->scoreThreshold = $scoreThreshold;
    }

    public function find(QueryDto $queryDto, int $size): FoundBannersCollection
    {
        $userHistory = $this->loadUserHistory($queryDto);
        $defined = $this->getDefinedRequireKeywords();
        $second = date('s');
        $query = new BaseQuery($queryDto, $defined);

        $params = [
            'index' => CampaignIndex::name(),
            'size' => $size,
            'client' => [
                'timeout' => 0.5,
                'connect_timeout' => 0.2
            ],
            'body' => [
                '_source' => false,
            ],
        ];

        if ($second % $this->expInterval === 0) {
            $queryBuilder = new ExpQueryBuilder($query, $this->expThreshold);
        } else {
            $queryBuilder = new QueryBuilder($query, $this->scoreThreshold, self::getSeenFrequencies($userHistory));
        }

//        $params['body']['explain'] = true;
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

        $this->updateUserHistory($userHistory, $collection);
        $this->saveUserHistory($queryDto, $userHistory);

        return $collection->random(self::BANNER_SIZE_RETURNED);
    }

    private static function getSeenFrequencies(array $userHistory): array
    {
        $seen = [];

        foreach ($userHistory as $entry) {
            if (isset($seen[$entry[self::HISTORY_ENTRY_CAMPAIGN_ID]])) {
                $seen[$entry[self::HISTORY_ENTRY_CAMPAIGN_ID]]++;
            } else {
                $seen[$entry[self::HISTORY_ENTRY_CAMPAIGN_ID]] = 1;
            }
        }

        return $seen;
    }

    private function getDefinedRequireKeywords(): array
    {
        $params = ['index' => CampaignIndex::name()];
        $response = $this->client->getMapping($params);

        $required = [];
        foreach ($response[CampaignIndex::name()]['mappings']['properties'] as $key => $def) {
            if (preg_match('/^filters:require:(.+)/', $key, $match)) {
                $required[] = $match[1];
            }
        }

        return $required;
    }

    private static function loadUserHistory(QueryDto $queryDto): array
    {
        $key = self::HISTORY_APC_KEY_PREFIX . ':' . $queryDto->getTrackingId();
        $history = (array)apcu_fetch($key);
        self::clearStaleEntries($history);
        return $history;
    }

    private static function saveUserHistory(QueryDto $queryDto, array $history): void
    {
        $key = self::HISTORY_APC_KEY_PREFIX . ':' . $queryDto->getTrackingId();
        self::clearStaleEntries($history);
        apc_store($key, $history, self::HISTORY_MAXAGE);
    }

    private static function clearStaleEntries(array &$history): void
    {
        $history = array_slice($history, -self::HISTORY_MAXENTRIES);
        $maxage = time() - self::HISTORY_MAXAGE;
        for ($i = 0, $n = count($history); $i < $n; $i++) {
            if ($history[$i][self::HISTORY_ENTRY_TIME] >= $maxage) {
                break;
            }
        }
        $history = array_slice($history, $i);
    }

    private function updateUserHistory(array &$history, FoundBannersCollection $collection): void
    {
        // It can be implemented only when we return one banner. Otherwise we do not know which one is displayed.
        if ($collection->count() > 0) {
            $history[] = [
                self::HISTORY_ENTRY_TIME => time(),
                self::HISTORY_ENTRY_CAMPAIGN_ID => $collection[0]->getCampaignId(),
            ];
        }
    }
}
