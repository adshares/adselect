<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;

class EventType
{
    public const VIEW = 'view';
    public const CLICK = 'click';

    private $type;

    public function __construct(string $type)
    {
        if ($type !== self::VIEW && $type !== self::CLICK) {
            throw new AdSelectRuntimeException(sprintf('Given event type (%s) is not valid.', $type));
        }

        $this->type = $type;
    }

    public static function createView(): self
    {
        return new self(self::VIEW);
    }

    public static function createClick(): self
    {
        return new self(self::CLICK);
    }

    public function toString(): string
    {
        return $this->type;
    }

    public function isView(): bool
    {
        return $this->type === self::VIEW;
    }

    public function isClick(): bool
    {
        return $this->type === self::CLICK;
    }
}
