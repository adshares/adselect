<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;

final class UnpaidEvents
{
    private $events;
    private $failedEvents = [];

    public function __construct(array $events)
    {
        $this->events = new EventCollection();

        foreach ($events as $event) {
            if ($this->isValid($event)) {
                try {
                    $event = new Event(
                        new Id($event['event_id']),
                        new Id($event['publisher_id']),
                        new Id($event['user_id']),
                        new Id($event['zone_id']),
                        new Id($event['campaign_id']),
                        new Id($event['banner_id']),
                        (array)$event['keywords']
                    );

                    $this->events->add($event);
                } catch (AdSelectRuntimeException $exception) {
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
            static function (Event $event) {
                return $event->getId();
            }
        )->toArray();
    }

    private function isValid(array $event): bool
    {
        if (!isset($event['event_id'])) {
            return false;
        }

        if (!isset($event['publisher_id'])) {
            return false;
        }

        if (!isset($event['user_id'])) {
            return false;
        }

        if (!isset($event['zone_id'])) {
            return false;
        }

        if (!isset($event['campaign_id'])) {
            return false;
        }

        if (!isset($event['banner_id'])) {
            return false;
        }

        if (!isset($event['keywords'])) {
            return false;
        }

        return true;
    }

    public function failedEvents(): array
    {
        return $this->failedEvents;
    }

    public function events(): EventCollection
    {
        return $this->events;
    }
}
