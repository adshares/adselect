<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Domain\ValueObject\Size;

class BannerFinderDto
{
    /** @var Id */
    private $publisherId;
    /** @var string */
    private $userId;
    /** @var Size */
    private $size;
    /** @var array */
    private $requireFilters;
    /** @var array */
    private $excludeFilters;
    /** @var array */
    private $keywords;

    public function __construct(Id $publisherId, string $userId, Size $size, array $filters = [], array $keywords = [])
    {
        $this->publisherId = $publisherId;
        $this->userId = $userId;
        $this->size = $size;
        $this->requireFilters = $filters['require'] ?? [];
        $this->excludeFilters = $filters['exclude'] ?? [];
        $this->keywords = $keywords;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getRequireFilters(): array
    {
        return $this->requireFilters;
    }

    public function getExcludeFilters(): array
    {
        return $this->excludeFilters;
    }
}
