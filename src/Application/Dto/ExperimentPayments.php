<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Exception\AdSelectRuntimeException;
use App\Domain\Model\ExperimentPayment;
use App\Domain\Model\ExperimentPaymentCollection;
use App\Lib\Exception\LibraryRuntimeException;
use App\Lib\ExtendedDateTime;

class ExperimentPayments
{
    protected const REQUIRED_FIELDS = [
        'id',
        'pay_time',
        'campaign_id',
        'paid_amount',
        'payer'
    ];

    protected ExperimentPaymentCollection $payments;
    /** @var ExperimentPayment[] */
    protected array $failedPayments = [];

    public function __construct(array $payments)
    {
        $this->payments = new ExperimentPaymentCollection();

        foreach ($payments as $payment) {
            if ($this->isValid($payment)) {
                try {
                    $payment = new ExperimentPayment(
                        $payment['id'],
                        $payment['campaign_id'],
                        ExtendedDateTime::createFromString($payment['pay_time']),
                        $payment['paid_amount'],
                        $payment['payer']
                    );
                    $this->payments->add($payment);
                } catch (AdSelectRuntimeException | LibraryRuntimeException $exception) {
                    $this->failedPayments[] = $payment;
                }
            } else {
                $this->failedPayments[] = $payment;
            }
        }
    }

    public function getPaymentIds(): array
    {
        return $this->payments
            ->map(fn (ExperimentPayment $payment) => $payment->getId())
            ->toArray();
    }

    public function failedPayments(): array
    {
        return $this->failedPayments;
    }

    public function payments(): ExperimentPaymentCollection
    {
        return $this->payments;
    }

    protected function isValid(array $payment): bool
    {
        $diff = array_diff(static::REQUIRED_FIELDS, array_keys($payment));

        if (count($diff) > 0) {
            return false;
        }

        return true;
    }
}
