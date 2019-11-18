<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Service\EventCollector as EventCollectorInterface;
use Adshares\AdSelect\Domain\Model\Click;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignStatsMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\ClickMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\EventMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\KeywordIntersectMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\KeywordMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\PaymentMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\UserHistoryMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIntersectIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use Adshares\Common\Exception\RuntimeException;
use function array_filter;
use function array_keys;

class EventCollector implements EventCollectorInterface
{
    private const ES_TYPE = 'COLLECT_UNPAID_EVENTS';
    private const APC_REFRESH_CLICKS_KEY = 'EventCollector.RefreshClicks';
    private const APC_REFRESH_PAYMENT_KEY = 'EventCollector.RefreshPayments';

    /** @var Client */
    private $client;
    /** @var int */
    private $bulkLimit;
    /** @var int */
    private $keywordIntersectThreshold;

    public function __construct(
        Client $client,
        int $bulkLimit = 500,
        int $keywordIntersectThreshold = 10
    ) {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2;
        $this->keywordIntersectThreshold = $keywordIntersectThreshold;
    }

    private function updateKeywords(EventCollection $events): void
    {
        /** @var Event $event */
        foreach ($events as $event) {
            if (!$event->getKeywords()) {
                return;
            }

            $flatKeywords = $event->flatKeywords();
            $mappedKeywords = KeywordMapper::map($flatKeywords, KeywordIndex::name());

            $response = $this->client->bulk($mappedKeywords, self::ES_TYPE);

            if (!isset($response['items'])) {
                return;
            }

            $actualKeywordsCount = [];
            foreach ($response['items'] as $response) {
                $id = $response['update']['_id'] ?? null;
                $newCount = $response['update']['get']['_source']['count'] ??
                    null;

                $keywordName = $flatKeywords[$id] ?? null;

                if ($keywordName) {
                    $actualKeywordsCount[$keywordName] = $newCount;
                }
            }

            if ($actualKeywordsCount) {
                $threshold = $this->keywordIntersectThreshold;
                $keywords = array_keys(
                    array_filter(
                        $actualKeywordsCount,
                        static function ($count) use ($threshold) {
                            return $count >= $threshold;
                        }
                    )
                );

                $this->updateKeywordsIntersect($keywords);
            }
        }
    }

    private function updateKeywordsIntersect(array $keywords): void
    {
        $keywordsSize = count($keywords);
        for ($i = 0; $i < $keywordsSize; $i++) {
            for ($j = $i + 1; $j < $keywordsSize; $j++) {
                $keywordA = $keywords[$i];
                $keywordB = $keywords[$j];

                $keywordIntersectMapper = KeywordIntersectMapper::map(
                    $keywordA,
                    $keywordB,
                    KeywordIntersectIndex::name()
                );

                $this->client->bulk($keywordIntersectMapper, self::ES_TYPE);
            }
        }
    }

    private function refreshIndexIfNeeded($cache_key)
    {
        if (apcu_fetch($cache_key)) {
            $this->client->refreshIndex(EventIndex::name());
            apcu_delete($cache_key);
        }
    }

    public function collectCases(EventCollection $events): void
    {
        $mappedEvents = [];

        /** @var Event $event */
        foreach ($events as $event) {
            $mappedUnpaidEvent = EventMapper::map($event, EventIndex::name());
            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            if (count($mappedEvents) >= $this->bulkLimit) {
                $response = $this->client->bulk($mappedEvents, self::ES_TYPE);
                if ($response['errors']) {
                    throw new ElasticSearchRuntime('Could not insert all cases');
                }
                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $response = $this->client->bulk($mappedEvents, self::ES_TYPE);
            if ($response['errors']) {
                throw new ElasticSearchRuntime('Could not insert all cases');
            }
        }

        if ($events->count() > 0) {
            $key = 'Adselect.EventFinder.LastCase';
            apcu_store($key, $events->last()->getId(), 300);
            apcu_store(self::APC_REFRESH_CLICKS_KEY, 1);
            apcu_store(self::APC_REFRESH_PAYMENT_KEY, 1);
        }
    }

    public function collectClicks(EventCollection $events): void
    {
        $this->refreshIndexIfNeeded(self::APC_REFRESH_CLICKS_KEY);

        $mappedEvents = [];

        /** @var Click $event */
        foreach ($events as $event) {
            $mappedUnpaidEvent = ClickMapper::map($event, EventIndex::name());
            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            if (count($mappedEvents) >= $this->bulkLimit) {
                $response = $this->client->bulk($mappedEvents, self::ES_TYPE);
//                if ($response['errors']) {
//                    throw new ElasticSearchRuntime('Could not update all clicks');
//                }
                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $response = $this->client->bulk($mappedEvents, self::ES_TYPE);
//            if ($response['errors']) {
//                throw new ElasticSearchRuntime('Could not update all clicks');
//            }
        }

        if ($events->count() > 0) {
            $key = 'Adselect.EventFinder.LastClick';
            apcu_store($key, $events->last()->getId(), 300);
            apcu_store(self::APC_REFRESH_PAYMENT_KEY, 1);
        }
    }

    public function collectPayments(EventCollection $events): void
    {
        $this->refreshIndexIfNeeded(self::APC_REFRESH_PAYMENT_KEY);

        $mappedEvents = [];

        /** @var Click $event */
        foreach ($events as $event) {
            $mappedUnpaidEvent = PaymentMapper::map($event, EventIndex::name());
            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            if (count($mappedEvents) >= $this->bulkLimit) {
                $response = $this->client->bulk($mappedEvents, self::ES_TYPE);
//                if ($response['errors']) {
//                    throw new ElasticSearchRuntime('Could not update all payments');
//                }

                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $response = $this->client->bulk($mappedEvents, self::ES_TYPE);
//            if ($response['errors']) {
//                throw new ElasticSearchRuntime('Could not update all payments');
//            }
        }

        if ($events->count() > 0) {
            $key = 'Adselect.EventFinder.LastPayment';
            apcu_store($key, $events->last()->getId(), 300);
        }
    }
}
