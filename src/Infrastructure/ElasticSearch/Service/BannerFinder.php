<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Dto\FoundBanner;
use Adshares\AdSelect\Application\Dto\FoundBannersCollection;
use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Application\Service\BannerFinder as BannerFinderInterface;
use Adshares\AdSelect\Application\Service\TimeService;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\BaseQuery;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\ExpQueryBuilder;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\QueryBuilder;
use Psr\Log\LoggerInterface;

class BannerFinder implements BannerFinderInterface
{
    private const BANNER_SIZE_RETURNED = 1;

    private const SOURCE_WEIGHTS_APC_KEY = 'Adselect.SourceWeights';

    private const HISTORY_APC_KEY_PREFIX = 'Adselect.UserHistory';
    private const HISTORY_ENTRY_TIME = 0;
    private const HISTORY_ENTRY_BANNER_ID = 1;
    private const HISTORY_MAXAGE = 3600 * 3;
    private const HISTORY_MAXENTRIES = 50;

    private Client $client;
    private TimeService $timeService;
    private LoggerInterface $logger;
    private float $experimentChance;

    public function __construct(
        Client $client,
        TimeService $timeService,
        float $experimentChance,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->timeService = $timeService;
        $this->logger = $logger;
        $this->experimentChance = $experimentChance;
    }

    public function find(
        QueryDto $queryDto,
        int $size
    ): FoundBannersCollection {
        $userHistory = $this->loadUserHistory($queryDto);
        $defined = $this->getDefinedRequireKeywords();
        $query = new BaseQuery($this->timeService, $queryDto, $defined);

        $params = [
            'index'  => BannerIndex::name(),
            'size'   => $size,
            'client' => [
                'timeout'         => 0.5,
                'connect_timeout' => 0.2
            ],
            'body'   => [
                '_source'         => false,
                'docvalue_fields' => ['banner.size', 'campaign_id'],
            ],
        ];

        $chance = (mt_rand(0, 999) / 1000);

        if ($chance < $this->experimentChance) {
            $this->logger->debug(
                sprintf(
                    '[BANNER FINDER] experiment < %s',
                    $this->experimentChance
                )
            );
            $queryBuilder = new ExpQueryBuilder($query);
        } else {
            $queryBuilder = new QueryBuilder(
                $query,
                (float)$queryDto->getZoneOption('min_cpm', 0.0),
                $this->getSeenOrder($userHistory)
            );
            $this->logger->debug('[BANNER FINDER] regular');
        }

        $params['body']['query'] = $queryBuilder->build();

        $this->logger->debug(
            sprintf(
                '[BANNER FINDER] sending a query: %s %s %s',
                $chance,
                $this->experimentChance,
                json_encode($params)
            )
        );

        $response = $this->client->search($params);

        $this->logger->debug(sprintf('[BANNER FINDER] response: %s', json_encode($response)));

        $collection = new FoundBannersCollection();

        if ($response['hits']['total']['value'] === 0) {
            return $collection;
        }

        foreach ($response['hits']['hits'] as $hit) {
            $collection->add(
                new FoundBanner(
                    $hit['fields']['campaign_id'][0],
                    $hit['_id'],
                    in_array($queryDto->getSize(), $hit['fields']['banner.size'], true)
                        ? $queryDto->getSize() : $hit['fields']['banner.size'][0],
                    fmod($hit['_score'], 100_000) / 1000
                )
            );
        }

        $this->updateUserHistory($userHistory, $collection);
        $this->saveUserHistory($queryDto, $userHistory);

        $result = $collection->random(self::BANNER_SIZE_RETURNED);

        $this->logger->debug(sprintf('[BANNER FINDER] response: %s', json_encode($result[0]->toArray())));

        return $result;
    }

    private function getSeenOrder(array $userHistory): array
    {
        $seen = [];

        foreach (array_reverse($userHistory) as $id => $entry) {
            $mod = ($id ** 2) / (($id + 1) ** 2);
            if (!isset($seen[$entry[self::HISTORY_ENTRY_BANNER_ID]])) {
                $seen[$entry[self::HISTORY_ENTRY_BANNER_ID]] = $mod;
            } else {
                $seen[$entry[self::HISTORY_ENTRY_BANNER_ID]] *= $mod;
            }
        }
        $this->logger->debug(sprintf('[BANNER FINDER] seen: %s', json_encode($seen)));
        return $seen;
    }

    private function getDefinedRequireKeywords(): array
    {
        $params = ['index' => BannerIndex::name()];
        $response = $this->client->getMapping($params);

        $required = [];
        foreach ($response[BannerIndex::name()]['mappings']['properties'] as $key => $def) {
            if (preg_match('/^filters:require:(.+)/', $key, $match)) {
                $required[] = $match[1];
            }
        }

        return $required;
    }

    private function loadUserHistory(QueryDto $queryDto): array
    {
        $key = self::HISTORY_APC_KEY_PREFIX . ':' . $queryDto->getTrackingId();
        $val = apcu_fetch($key);
        $history = $val ?: [];
        $this->clearStaleEntries($history);
        return $history;
    }

    private function saveUserHistory(
        QueryDto $queryDto,
        array $history
    ): void {
        $key = self::HISTORY_APC_KEY_PREFIX . ':' . $queryDto->getTrackingId();
        $this->clearStaleEntries($history);
        apcu_store($key, $history, self::HISTORY_MAXAGE);
    }

    private function clearStaleEntries(array &$history): void
    {
        $history = array_slice($history, -self::HISTORY_MAXENTRIES);
        $maxAge = $this->timeService->getDateTime()->getTimestamp() - self::HISTORY_MAXAGE;
        for ($i = 0, $n = count($history); $i < $n; $i++) {
            if ($history[$i][self::HISTORY_ENTRY_TIME] >= $maxAge) {
                break;
            }
        }
        $history = array_slice($history, $i);
    }

    private function updateUserHistory(
        array &$history,
        FoundBannersCollection $collection
    ): void {
        // It can be implemented only when we return one banner. Otherwise, we do not know which one is displayed.
        if ($collection->count() > 0) {
            $history[] = [
                self::HISTORY_ENTRY_TIME      => $this->timeService->getDateTime()->getTimestamp(),
                self::HISTORY_ENTRY_BANNER_ID => $collection[0]->getBannerId(),
            ];
        }
    }
}
