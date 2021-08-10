<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Domain\ValueObject;

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
}
