<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Dto\FoundEvent;
use Adshares\AdSelect\Application\Exception\EventNotFound;
use Adshares\AdSelect\Application\Service\EventFinder as EventFinderInterface;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Psr\Log\LoggerInterface;

class EventFinder implements EventFinderInterface
{
    /** @var Client */
    private $client;
    /** @var array */
    private $params;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;

        $this->params = [
            'index' => EventIndex::name(),
            'body' => [
                '_source' => false,
                'docvalue_fields' => ['id', 'click_id', 'last_payment_id'],
                'size' => 1,
                'query' => [],
                'sort' => [],
            ],
        ];

        $this->logger = $logger;
    }

    public function findLastUnpaidEvent(): FoundEvent
    {
        $query = [
            'bool' => [
                'must_not' => [
                    'exists' => [
                        'field' => 'payment_id',
                    ],
                ],
            ],
        ];

        $sort = [
            [
                'id' => [
                    'order' => 'desc'
                ],
            ],
        ];

        $params = $this->params;
        $params['body']['query'] = $query;
        $params['body']['sort'] = $sort;

        $this->logger->debug(sprintf('[EVENT FINDER] (paid) sending a query: %s', json_encode($params)));

        $response = $this->client->search($params);
        $data = $response['hits']['hits'][0]['fields'] ?? null;

        if (!$data) {
            throw new EventNotFound('No unpaid events.');
        }

        return new FoundEvent(
            $data['id'][0],
            $data['case_id'][0],
            $data['publisher_id'][0],
            $data['paid_amount'][0],
            $data['time'][0]
        );
    }

    public function findLastPaidEvent(): FoundEvent
    {
        $query = [
            'bool' => [
                'must' => [
                    'exists' => [
                        'field' => 'payment_id',
                    ],
                ],
            ],
        ];

        $sort = [
            [
                'payment_id' => [
                    'order' => 'desc'
                ],
            ],
        ];

        $params = $this->params;
        $params['body']['query'] = $query;
        $params['body']['sort'] = $sort;

        $this->logger->debug(sprintf('[EVENT FINDER] (paid) sending a query: %s', json_encode($params)));

        $response = $this->client->search($params);
        $data = $response['hits']['hits'][0]['fields'] ?? null;

        if (!$data) {
            throw new EventNotFound('No paid events.');
        }

        return new FoundEvent(
            $data['id'][0],
            $data['case_id'][0],
            $data['publisher_id'][0],
            $data['paid_amount'][0],
            $data['time'][0],
            $data['payment_id'][0]
        );
    }


    public function findLastCase(): FoundEvent
    {
        $key = 'Adselect.EventFinder.LastCase';
        $found_id = apcu_fetch($key);
        if (!$found_id) {
            $query = [
                'bool' => [
                    'must' => [
                        'exists' => [
                            'field' => 'id',
                        ],
                    ],
                ],
            ];

            $sort = [
                [
                    'id' => [
                        'order' => 'desc'
                    ],
                ],
            ];

            $params = $this->params;
            $params['body']['query'] = $query;
            $params['body']['sort'] = $sort;

            $this->logger->debug(sprintf('[EVENT FINDER] (last case) sending a query: %s', json_encode($params)));

            $response = $this->client->search($params);
            $data = $response['hits']['hits'][0]['fields'] ?? null;

            if (!$data) {
                throw new EventNotFound('No events.');
            }

            $found_id = $data['id'][0];
            apcu_store($key, $found_id, 300);
        }
        return new FoundEvent(
            $found_id
        );
    }

    public function findLastClick(): FoundEvent
    {
        $key = 'Adselect.EventFinder.LastClick';
        $found_id = apcu_fetch($key);
        if (!$found_id) {
            $query = [
                'bool' => [
                    'must' => [
                        'exists' => [
                            'field' => 'click_id',
                        ],
                    ],
                ],
            ];

            $sort = [
                [
                    'click_id' => [
                        'order' => 'desc'
                    ],
                ],
            ];

            $params = $this->params;
            $params['body']['query'] = $query;
            $params['body']['sort'] = $sort;

            $this->logger->debug(sprintf('[EVENT FINDER] (last click) sending a query: %s', json_encode($params)));

            $response = $this->client->search($params);
            $data = $response['hits']['hits'][0]['fields'] ?? null;

            if (!$data) {
                throw new EventNotFound('No events.');
            }

            $found_id = $data['click_id'][0];
            apcu_store($key, $found_id, 300);
        }
        return new FoundEvent(
            $found_id
        );
    }

    public function findLastPayment(): FoundEvent
    {
        $key = 'Adselect.EventFinder.LastPayment';
        $found_id = apcu_fetch($key);
        if (!$found_id) {
            $query = [
                'bool' => [
                    'must' => [
                        'exists' => [
                            'field' => 'last_payment_id',
                        ],
                    ],
                ],
            ];

            $sort = [
                [
                    'last_payment_id' => [
                        'order' => 'desc'
                    ],
                ],
            ];

            $params = $this->params;
            $params['body']['query'] = $query;
            $params['body']['sort'] = $sort;

            $this->logger->debug(sprintf('[EVENT FINDER] (last case) sending a query: %s', json_encode($params)));

            $response = $this->client->search($params);
            $data = $response['hits']['hits'][0]['fields'] ?? null;

            if (!$data) {
                throw new EventNotFound('No events.');
            }
            $found_id = $data['last_payment_id'][0];
            apcu_store($key, $found_id, 300);
        }
        return new FoundEvent(
            $found_id
        );
    }
}
