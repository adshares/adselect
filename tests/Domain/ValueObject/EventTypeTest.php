<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\ValueObject\EventType;
use PHPUnit\Framework\TestCase;

final class EventTypeTest extends TestCase
{
    public function testWhenViewType(): void
    {
        $eventType = EventType::createView();

        $this->assertEquals('view', $eventType->toString());
        $this->assertTrue($eventType->isView());
        $this->assertFalse($eventType->isClick());
    }

    public function testWhenClickType(): void
    {
        $eventType = EventType::createClick();

        $this->assertEquals('click', $eventType->toString());
        $this->assertTrue($eventType->isClick());
        $this->assertFalse($eventType->isView());
    }

    public function testWhenNotValid(): void
    {
        $this->expectException(AdSelectRuntimeException::class);
        new EventType('non-existent-type');
    }
}
