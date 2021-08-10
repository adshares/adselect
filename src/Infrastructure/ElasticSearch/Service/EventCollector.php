<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Service\EventCollector as EventCollectorInterface;
use Adshares\AdSelect\Domain\Model\Click;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Domain\Model\Payment;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\ClickMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\EventMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\PaymentMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;

class EventCollector implements EventCollectorInterface
{
    private const ES_TYPE = 'COLLECT_UNPAID_EVENTS';
    private const ES_TYPE_CLICK = 'COLLECT_CLICKS';
    private const ES_TYPE_PAYMENT = 'COLLECT_PAYMENTS';
    private const APC_REFRESH_CLICKS_KEY = 'EventCollector.RefreshClicks';
    private const APC_REFRESH_PAYMENT_KEY = 'EventCollector.RefreshPayments';

    /** @var Client */
    private $client;
    /** @var int */
    private $bulkLimit;

    public function __construct(
        Client $client,
        int $bulkLimit = 500
    ) {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2;
    }

    private function refreshIndexIfNeeded($cache_key)
    {
        if (apcu_fetch($cache_key)) {
            $this->client->refreshIndex(EventIndex::name());
            apcu_delete($cache_key);
        }
    }

    private function getLastUpdatedOffset(array $elasticResponse): ?int
    {
        for ($i = count($elasticResponse['items']) - 1; $i >= 0; $i--) {
            if ($elasticResponse['items'][$i]['update']['status'] == 200) {
                return $i;
            }
        }
        return null;
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

        $lastId = 0;
        $bulkOffset = 0;
        $mappedEvents = [];

        /** @var Click $event */
        foreach ($events as $event) {
            $mappedUnpaidEvent = ClickMapper::map($event, EventIndex::name());
            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            if (count($mappedEvents) >= $this->bulkLimit) {
                $response = $this->client->bulk($mappedEvents, self::ES_TYPE_CLICK);
                $lastOffset = $this->getLastUpdatedOffset($response);
                if ($lastOffset !== null) {
                    $lastId = $events[$bulkOffset + $lastOffset]->getId();
                }
                $bulkOffset += count($mappedEvents) / 2;
                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $response = $this->client->bulk($mappedEvents, self::ES_TYPE_CLICK);
            $lastOffset = $this->getLastUpdatedOffset($response);
            if ($lastOffset !== null) {
                $lastId = $events[$bulkOffset + $lastOffset]->getId();
            }
        }

        if ($lastId > 0) {
            $key = 'Adselect.EventFinder.LastClick';
            apcu_store($key, $lastId, 300);
            apcu_store(self::APC_REFRESH_PAYMENT_KEY, 1);
        } else {
            throw new ElasticSearchRuntime('Could not insert any clicks');
        }
    }

    public function collectPayments(EventCollection $events): void
    {
        $this->refreshIndexIfNeeded(self::APC_REFRESH_PAYMENT_KEY);

        $lastId = 0;
        $bulkOffset = 0;
        $mappedEvents = [];

        /** @var Payment $event */
        foreach ($events as $event) {
            $mappedUnpaidEvent = PaymentMapper::map($event, EventIndex::name());
            $mappedEvents[] = $mappedUnpaidEvent['index'];
            $mappedEvents[] = $mappedUnpaidEvent['data'];

            if (count($mappedEvents) >= $this->bulkLimit) {
                $response = $this->client->bulk($mappedEvents, self::ES_TYPE_PAYMENT);
                $lastOffset = $this->getLastUpdatedOffset($response);
                if ($lastOffset !== null) {
                    $lastId = $events[$bulkOffset + $lastOffset]->getId();
                }
                $bulkOffset += count($mappedEvents) / 2;
                $mappedEvents = [];
            }
        }

        if ($mappedEvents) {
            $response = $this->client->bulk($mappedEvents, self::ES_TYPE_PAYMENT);
            $lastOffset = $this->getLastUpdatedOffset($response);
            if ($lastOffset !== null) {
                $lastId = $events[$bulkOffset + $lastOffset]->getId();
            }
        }

        if ($events->count() > 0) {
            $key = 'Adselect.EventFinder.LastPayment';
            apcu_store($key, $lastId, 300);
        }
    }
}
