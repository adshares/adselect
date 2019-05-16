<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Service\EventCollector as ImpressionCollectorInterface;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\EventMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\UserHistoryMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
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

        $mappedEvents = [];

        foreach ($events as $event) {
            $mappedUnpaidEvent = EventMapper::map($event, EventIndex::INDEX);
            $mappedUserHistory = UserHistoryMapper::map($event, UserHistoryIndex::INDEX);

            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            $mappedEvents[] = $mappedUserHistory['index'];
            $mappedEvents[] = $mappedUserHistory['data'];

            if (count($mappedEvents) === $this->bulkLimit) {
                $this->client->bulk($mappedEvents, self::ES_TYPE);

                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $this->client->bulk($mappedEvents, self::ES_TYPE);
        }
    }
}
