<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Unit\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class IdTest extends TestCase
{
    public function testWhenIdIsNotValid(): void
    {
        $this->expectException(AdSelectRuntimeException::class);
        new Id('1234qwe');
    }

    public function testWhenIdIsValid(): void
    {
        $id = new Id('43c567e1396b4cadb52223a51796fd01');

        $this->assertEquals('43c567e1396b4cadb52223a51796fd01', $id->toString());
    }

    public function testEquals(): void
    {
        $id1 = new Id('43c567e1396b4cadb52223a51796fd01');
        $id2 = new Id('43c567e1396b4cadb52223a51796fd01');

        $this->assertTrue($id1->equals($id2));
    }

    public function testNotEquals(): void
    {
        $id1 = new Id('43c567e1396b4cadb52223a51796fd01');
        $id2 = new Id('12c567e1396b4cadb52223a51796fd10');

        $this->assertFalse($id1->equals($id2));
    }
}
