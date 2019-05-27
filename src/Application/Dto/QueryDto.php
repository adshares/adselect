<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Domain\ValueObject\Size;

final class QueryDto
{
    /** @var Id */
    private $publisherId;
    /** @var Id */
    private $userId;
    /** @var Size */
    private $size;
    /** @var array */
    private $requireFilters;
    /** @var array */
    private $excludeFilters;
    /** @var array */
    private $keywords;

    public function __construct(Id $publisherId, Id $userId, Size $size, array $filters = [], array $keywords = [])
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

    public function getUserId(): string
    {
        return $this->userId->toString();
    }

    public function getSize(): string
    {
        return $this->size->toString();
    }

    public static function fromArray(array $input): self
    {
        if (!isset($input['publisher_id'])) {
            throw new ValidationDtoException('Field `publisher_id` is required.');
        }

        if (!isset($input['user_id'])) {
            throw new ValidationDtoException('Field `user_id` is required.');
        }

        if (!isset($input['banner_size'])) {
            throw new ValidationDtoException('Field `banner_size` is required.');
        }

        if (!isset($input['keywords'])) {
            throw new ValidationDtoException('Field `keywords` is required.');
        }

        if (!isset($input['banner_filters'])) {
            throw new ValidationDtoException('Field `banner_filters` is required.');
        }

        try {
            return new self(
                new Id($input['publisher_id']),
                new Id($input['user_id']),
                Size::fromString($input['banner_size']),
                $input['banner_filters'],
                $input['keywords']
            );
        } catch (AdSelectRuntimeException $exception) {
            throw new ValidationDtoException($exception->getMessage());
        }
    }
}
