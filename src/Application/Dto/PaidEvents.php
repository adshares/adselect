<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

class PaidEvents extends Events
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

        if (!isset($event['time'])) {
            return false;
        }

        if (!isset($event['paid_amount'])) {
            return false;
        }

        return true;
    }
}
