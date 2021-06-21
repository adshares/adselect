<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\Model\Payment;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Lib\Exception\LibraryRuntimeException;
use Adshares\AdSelect\Lib\ExtendedDateTime;

class Payments
{
    protected const REQUIRED_FIELDS = [
        'id',
        'pay_time',
        'case_id',
        'paid_amount',
        'payer'
    ];
    protected $events;
    protected $failedEvents = [];

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
