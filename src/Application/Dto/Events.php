<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\Exception\LibraryRuntimeException;
use Adshares\AdSelect\Lib\ExtendedDateTime;

abstract class Events
{
    protected $events;
    protected $failedEvents = [];

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
                        $event['keywords'] ?? [],
                        ExtendedDateTime::createFromString($event['time']),
                        (float)($event['paid_amount'] ?? 0)
                    );

                    if (!$this->events->eventExists($event)) {
                        $this->events->add($event);
                    }
                } catch (AdSelectRuntimeException|LibraryRuntimeException $exception) {
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

    public function failedEvents(): array
    {
        return $this->failedEvents;
    }

    public function events(): EventCollection
    {
        return $this->events;
    }

    abstract protected function isValid(array $event): bool;
}
