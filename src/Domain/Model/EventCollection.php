<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;

final class EventCollection extends ArrayCollection
{
    public function flattenKeywords(): array
    {
        $keywords = [];

        /** @var Event $value */
        foreach ($this as $value) {
            $keywordsFromEvent = $value->flatKeywords();

            foreach ($keywordsFromEvent as $id => $keyword) {
                $keywords[$id] = $keyword;
            }
        }

        return $keywords;
    }
}
