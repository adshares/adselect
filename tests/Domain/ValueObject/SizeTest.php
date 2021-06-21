<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\ValueObject\Size;
use PHPUnit\Framework\TestCase;

final class SizeTest extends TestCase
{
    public function testFromString(): void
    {
        $size = new Size('200x65');

        $this->assertEquals(200, $size->getWidth());
        $this->assertEquals(65, $size->getHeight());
        $this->assertEquals('200x65', $size->toString());
    }
}
