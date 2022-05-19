<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Unit\Domain\Model;

use Adshares\AdSelect\Domain\Model\IdCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class IdCollectionTest extends TestCase
{
    public function testMultiplyAdding(): void
    {
        $id1 = new Id('00000000000000000000000000000001');
        $id2 = new Id('00000000000000000000000000000002');
        $id3 = new Id('00000000000000000000000000000003');
        $id4 = new Id('00000000000000000000000000000004');

        $this->assertCount(4, new IdCollection($id1, $id2, $id3, $id4));
    }
}
