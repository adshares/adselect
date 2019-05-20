<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Service\EventCollector as ImpressionCollectorInterface;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\EventMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\KeywordIntersectMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\KeywordMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\UserHistoryMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIntersectIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use function array_filter;
use function array_keys;

class EventCollector implements ImpressionCollectorInterface
{
    private const ES_TYPE = 'COLLECT_UNPAID_EVENTS';

    /** @var Client */
    private $client;
    /** @var int */
    private $bulkLimit;
    /** @var int */
    private $keywordIntersectThreshold;

    public function __construct(Client $client, int $bulkLimit = 500, int $keywordIntersectThreshold = 10)
    {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2;
        $this->keywordIntersectThreshold = $keywordIntersectThreshold;
    }

    public function collect(EventCollection $events): void
    {
        $this->createIndexesIfNeeded();

        $mappedEvents = [];

        /** @var Event $event */
        foreach ($events as $event) {
            $mappedUnpaidEvent = EventMapper::map($event, EventIndex::INDEX);
            $mappedUserHistory = UserHistoryMapper::map($event, UserHistoryIndex::INDEX);

            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            $mappedEvents[] = $mappedUserHistory['index'];
            $mappedEvents[] = $mappedUserHistory['data'];

            if (count($mappedEvents) >= $this->bulkLimit) {
                $this->client->bulk($mappedEvents, self::ES_TYPE);

                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $this->client->bulk($mappedEvents, self::ES_TYPE);
        }

        $this->updateKeywords($events);
    }

    private function createIndexesIfNeeded(): void
    {
        if (!$this->client->eventIndexExists()) {
            $this->client->createEventIndex();
        }

        if (!$this->client->userHistoryIndexExists()) {
            $this->client->createUserHistory();
        }

        if (!$this->client->keywordIndexExists()) {
            $this->client->createKeywordIndex();
        }

        if (!$this->client->keywordIntersectionIndexExists()) {
            $this->client->createKeywordIntersectionIndex();
        }
    }

    private function updateKeywords(EventCollection $events): void
    {
        /** @var Event $event */
        foreach ($events as $event) {
            $flatKeywords = $event->flatKeywords();
            $mappedKeywords = KeywordMapper::map($flatKeywords, KeywordIndex::INDEX);

            $response = $this->client->bulk($mappedKeywords, self::ES_TYPE);

            $actualKeywordsCount = [];
            foreach ($response['items'] as $response) {
                $id = $response['update']['_id'] ?? null;
                $newCount = $response['update']['get']['_source']['count'] ?? null;

                $keywordName = $flatKeywords[$id] ?? null;

                if ($keywordName) {
                    $actualKeywordsCount[$keywordName] = $newCount;
                }
            }

            if ($actualKeywordsCount) {
                $threshold = $this->keywordIntersectThreshold;
                $keywords = array_keys(array_filter(
                    $actualKeywordsCount,
                    static function ($count) use ($threshold) {
                        return $count >= $threshold;
                    }
                ));

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
                    KeywordIntersectIndex::INDEX
                );

                $this->client->bulk($keywordIntersectMapper, self::ES_TYPE);
            }
        }
    }
}
