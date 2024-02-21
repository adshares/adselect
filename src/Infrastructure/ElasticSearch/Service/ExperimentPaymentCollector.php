<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Service;

use App\Application\Service\ExperimentPaymentCollector as ExperimentPaymentCollectorInterface;
use App\Domain\Model\ExperimentPaymentCollection;
use App\Infrastructure\ElasticSearch\Client;
use App\Infrastructure\ElasticSearch\Mapper\ExperimentPaymentMapper;
use App\Infrastructure\ElasticSearch\Mapping\ExperimentPaymentIndex;

class ExperimentPaymentCollector implements ExperimentPaymentCollectorInterface
{
    private const CACHE_KEY_LAST_PAYMENT_ID = 'Adselect.ExperimentPaymentFinder.LastPayment';
    private const ES_TYPE_PAYMENT = 'EXPERIMENT PAYMENTS';

    private Client $client;
    private int $bulkLimit;

    public function __construct(
        Client $client,
        int $bulkLimit = 500
    ) {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2;
    }

    private function getLastOffset(array $elasticResponse): ?int
    {
        for ($index = count($elasticResponse['items']) - 1; $index >= 0; $index--) {
            if ($elasticResponse['items'][$index]['index']['status'] == 201) {
                return $index;
            }
        }
        return null;
    }

    public function collectPayments(ExperimentPaymentCollection $payments): void
    {
        $lastId = null;
        $bulkOffset = 0;
        $mappedPayments = [];

        foreach ($payments as $payment) {
            $mapped = ExperimentPaymentMapper::map($payment, ExperimentPaymentIndex::name());
            $mappedPayments[] = $mapped['index'];
            $mappedPayments[] = $mapped['data'];

            if (count($mappedPayments) >= $this->bulkLimit) {
                $response = $this->client->bulk($mappedPayments, self::ES_TYPE_PAYMENT);
                $lastOffset = $this->getLastOffset($response);
                if (null !== $lastOffset) {
                    $lastId = $payments[$bulkOffset + $lastOffset]->getId();
                }
                $bulkOffset += count($mappedPayments) / 2;
                $mappedPayments = [];
            }
        }

        if ($mappedPayments) {
            $response = $this->client->bulk($mappedPayments, self::ES_TYPE_PAYMENT);
            $lastOffset = $this->getLastOffset($response);
            if (null !== $lastOffset) {
                $lastId = $payments[$bulkOffset + $lastOffset]->getId();
            }
        }

        if (null !== $lastId) {
            apcu_store(self::CACHE_KEY_LAST_PAYMENT_ID, $lastId, 300);
        }
    }
}
