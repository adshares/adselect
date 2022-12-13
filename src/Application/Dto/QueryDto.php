<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Application\Exception\ValidationDtoException;
use App\Domain\Exception\AdSelectRuntimeException;
use App\Domain\ValueObject\Id;
use App\Domain\ValueObject\Size;

final class QueryDto
{
    private Id $publisherId;
    private Id $siteId;
    private Id $zoneId;
    private Id $userId;
    private array $scopes;
    private array $requireFilters;
    private array $excludeFilters;
    private array $keywords;
    private Id $trackingId;
    private array $zoneOptions;

    public function __construct(
        Id $publisherId,
        Id $siteId,
        Id $zoneId,
        Id $userId,
        Id $trackingId,
        array $scopes,
        array $zone_options = [],
        array $filters = [],
        array $keywords = []
    ) {
        $this->publisherId = $publisherId;
        $this->siteId = $siteId;
        $this->zoneId = $zoneId;
        $this->userId = $userId;
        $this->trackingId = $trackingId;
        $this->scopes = $scopes;
        $this->requireFilters = $filters['require'] ?? [];
        $this->excludeFilters = $filters['exclude'] ?? [];
        $this->keywords = $keywords;
        $this->zoneOptions = $zone_options;
    }

    public function getPublisherId(): Id
    {
        return $this->publisherId;
    }

    public function getSiteId(): Id
    {
        return $this->siteId;
    }

    public function getZoneId(): Id
    {
        return $this->zoneId;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getZoneOption($key, $default = null)
    {
        return $this->zoneOptions[$key] ?? $default;
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

    public function getTrackingId(): string
    {
        return $this->trackingId->toString();
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public static function fromArray(array $input): self
    {
        if (!isset($input['publisher_id'])) {
            throw new ValidationDtoException('Field `publisher_id` is required.');
        }

        if (!isset($input['site_id'])) {
            throw new ValidationDtoException('Field `site_id` is required.');
        }

        if (!isset($input['zone_id'])) {
            throw new ValidationDtoException('Field `zone_id` is required.');
        }

        if (!isset($input['user_id']) || empty($input['user_id'])) {
            throw new ValidationDtoException('Field `user_id` is required.');
        }

        if (!isset($input['tracking_id']) || empty($input['tracking_id'])) {
            throw new ValidationDtoException('Field `tracking_id` is required.');
        }

        if (!isset($input['scopes'])) {
            if (!isset($input['banner_size'])) {
                throw new ValidationDtoException('Field `scopes` is required.');
            }
            $input['scopes'] = [$input['banner_size']];
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
                new Id($input['site_id']),
                new Id($input['zone_id']),
                new Id($input['user_id']),
                new Id($input['tracking_id']),
                $input['scopes'],
                $input['zone_options'] ?? [],
                $input['banner_filters'],
                $input['keywords']
            );
        } catch (AdSelectRuntimeException $exception) {
            throw new ValidationDtoException($exception->getMessage());
        }
    }
}
