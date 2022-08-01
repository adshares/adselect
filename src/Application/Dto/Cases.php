<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Exception\AdSelectRuntimeException;
use App\Domain\Model\Event;
use App\Domain\Model\EventCollection;
use App\Domain\ValueObject\Id;
use App\Lib\Exception\LibraryRuntimeException;
use App\Lib\ExtendedDateTime;

class Cases
{
    protected const REQUIRED_FIELDS = [
        'id',
        'created_at',
        'publisher_id',
        'site_id',
        'zone_id',
        'campaign_id',
        'banner_id',
        'impression_id',
        'tracking_id',
        'user_id'
    ];
    protected EventCollection $events;
    /** @var array|Event[] */
    protected array $failedEvents = [];

    public function __construct(array $events)
    {
        $this->events = new EventCollection();

        foreach ($events as $event) {
            if ($this->isValid($event)) {
                try {
                    $event = new Event(
                        $event['id'],
                        ExtendedDateTime::createFromString($event['created_at']),
                        new Id($event['publisher_id']),
                        new Id($event['site_id'] ?? $event['publisher_id']),
                        new Id($event['zone_id']),
                        new Id($event['campaign_id']),
                        new Id($event['banner_id']),
                        new Id($event['impression_id']),
                        new Id($event['tracking_id']),
                        new Id($event['user_id']),
                        $event['keywords'] ?? []
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

    protected function isValid(array $event): bool
    {
        $diff = array_diff(static::REQUIRED_FIELDS, array_keys($event));

        if (count($diff) > 0) {
            return false;
        }

        return true;
    }
}
