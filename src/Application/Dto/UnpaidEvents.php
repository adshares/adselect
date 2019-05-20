<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\EventCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\Exception\LibraryRuntimeException;
use Adshares\AdSelect\Lib\ExtendedDateTime;

final class UnpaidEvents extends Events
{
    protected function isValid(array $event): bool
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

        if (!isset($event['time'])) {
            return false;
        }

        return true;
    }
}
