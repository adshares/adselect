<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Dto;

use Doctrine\Common\Collections\ArrayCollection;

final class FoundBannersCollection extends ArrayCollection
{
    public function random(int $size = 1): self
    {
        $data = $this->toArray();
        $count = min($size, count($data));
        $random = [];
        $randKeys = (array)array_rand($data, $count);
        foreach ($randKeys as $key) {
            $random[] = $data[$key];
        }

        return new self($random);
    }

    public function limit(int $limit): self
    {
        return new self($this->slice(0, $limit));
    }
}
