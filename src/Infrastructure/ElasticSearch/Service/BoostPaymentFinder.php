<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Service;

use App\Application\Dto\FoundBoostPayment;
use App\Application\Exception\BoostPaymentNotFound;
use App\Application\Service\BoostPaymentFinder as BoostPaymentFinderInterface;
use App\Infrastructure\ElasticSearch\Client;
use App\Infrastructure\ElasticSearch\Mapping\BoostPaymentIndex;
use Psr\Log\LoggerInterface;

class BoostPaymentFinder implements BoostPaymentFinderInterface
{
    private const CACHE_KEY_LAST_PAYMENT_ID = 'Adselect.BoostPaymentFinder.LastPayment';

    private Client $client;
    private LoggerInterface $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function findLastPayment(): FoundBoostPayment
    {
        $foundId = apcu_fetch(self::CACHE_KEY_LAST_PAYMENT_ID);
        if (!$foundId) {
            $params = [
                'index' => BoostPaymentIndex::name(),
                'body' => [
                    '_source' => false,
                    'docvalue_fields' => ['id'],
                    'size' => 1,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'exists' => [
                                    'field' => 'id',
                                ],
                            ],
                        ],
                    ],
                    'sort' => [
                        [
                            'id' => [
                                'order' => 'desc',
                            ],
                        ],
                    ],
                ],
            ];

            $this->logger->debug(
                sprintf('[BOOST PAYMENT FINDER] (last case) sending a query: %s', json_encode($params))
            );

            $response = $this->client->search($params);
            $data = $response['hits']['hits'][0]['fields'] ?? null;

            if (!$data) {
                throw new BoostPaymentNotFound();
            }
            $foundId = $data['id'][0];
            apcu_store(self::CACHE_KEY_LAST_PAYMENT_ID, $foundId, 300);
        }

        return new FoundBoostPayment($foundId);
    }
}
