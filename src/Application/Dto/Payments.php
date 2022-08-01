<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Exception\AdSelectRuntimeException;
use App\Domain\Model\Payment;
use App\Domain\Model\EventCollection;
use App\Lib\Exception\LibraryRuntimeException;
use App\Lib\ExtendedDateTime;

class Payments
{
    protected const REQUIRED_FIELDS = [
        'id',
        'pay_time',
        'case_id',
        'paid_amount',
        'payer'
    ];
    protected EventCollection $events;
    /** @var array|Payment[] */
    protected array $failedEvents = [];

    public function __construct(array $events)
    {
        $this->events = new EventCollection();

        foreach ($events as $event) {
            if ($this->isValid($event)) {
                try {
                    $event = new Payment(
                        $event['id'],
                        ExtendedDateTime::createFromString($event['pay_time']),
                        $event['case_id'],
                        $event['paid_amount'],
                        $event['payer']
                    );

                    $this->events->add($event);
                } catch (AdSelectRuntimeException | LibraryRuntimeException $exception) {
                    $this->failedEvents[] = $event;
                }
            } else {
                $this->failedEvents[] = $event;
            }
        }
    }

    public function getEventsIds(): array
    {
        return $this->events->map(
            static function (Payment $event) {
                return $event->getId();
            }
        )->toArray();
    }

    public function failedEvents(): array
    {
        return $this->failedEvents;
    }

    public function events(): EventCollection
    {
        return $this->events;
    }

    protected function isValid(array $event): bool
    {
        $diff = array_diff(static::REQUIRED_FIELDS, array_keys($event));

        if (count($diff) > 0) {
            return false;
        }

        return true;
    }
}
