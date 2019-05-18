<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Service\EventCollector as ImpressionCollectorInterface;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\EventMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\KeywordMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\UserHistoryMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\KeywordIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;

class EventCollector implements ImpressionCollectorInterface
{
    private const ES_TYPE = 'COLLECT_UNPAID_EVENTS';

    /** @var Client */
    private $client;
    /** @var int */
    private $bulkLimit;

    public function __construct(Client $client, int $bulkLimit = 2)
    {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2;
    }

    public function collect(EventCollection $events): void
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

    private function updateKeywords(EventCollection $events): array
    {
        $mapped = [];
        $after = [];
        $allFlatKeywords = $events->flattenKeywords();

        /** @var Event $event */
        foreach ($events as $event) {
            $mappedKeywords = KeywordMapper::map($event, KeywordIndex::INDEX);


            foreach ($mappedKeywords as $mappedKeyword) {
                $mapped[] = $mappedKeyword;
            }

            if (count($mapped) >= $this->bulkLimit) {
                $response = $this->client->bulk($mapped, self::ES_TYPE);

                foreach ($response['items'] as $response) {
                    $id = $response['update']['_id'];
                    $newCount = $response['update']['get']['_source']['count'];

                    $keywordName = $allFlatKeywords[$id] ?? null;

                    if ($keywordName) {
                        $after[$keywordName] = $newCount;
                    }
                }

                $mapped = [];
            }
        }

        if ($mapped) {
            $response = $this->client->bulk($mapped, self::ES_TYPE);
            foreach ($response['items'] as $response) {
                $id = $response['update']['_id'];
                $newCount = $response['update']['get']['_source']['count'];

                $keywordName = $allFlatKeywords[$id] ?? null;

                if ($keywordName) {
                    $after[$keywordName] = $newCount;
                }
            }
        }

        return $after;
    }

    private function actualEventsCountValue(array $response, array $flatKeywords)
    {
        return 1;
    }
}
