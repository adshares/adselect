<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Unit\Domain\ValueObject;

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

    public function testSizeWithoutDimension(): void
    {
        $size = new Size('cube');

        $this->assertEquals(0, $size->getWidth());
        $this->assertEquals(0, $size->getHeight());
        $this->assertEquals('cube', $size->toString());
    }

    public function testInvalidSize(): void
    {
        $size = new Size('widthxheight');

        $this->assertEquals(0, $size->getWidth());
        $this->assertEquals(0, $size->getHeight());
        $this->assertEquals('widthxheight', $size->toString());
    }
}
