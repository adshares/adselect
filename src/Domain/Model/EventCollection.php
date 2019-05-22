<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;

final class EventCollection extends ArrayCollection
{
    public function eventExists(Event $event): bool
    {
        foreach ($this as $item) {
            if ($item->equals($event)) {
                return true;
            }
        }

        return false;
    }
}
