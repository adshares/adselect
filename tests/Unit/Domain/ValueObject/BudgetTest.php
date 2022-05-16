<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Unit\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\ValueObject\Budget;
use PHPUnit\Framework\TestCase;

final class BudgetTest extends TestCase
{
    public function testNegativeBudget(): void
    {
        self::expectException(AdSelectRuntimeException::class);

        new Budget(-100, null, null);
    }

    public function testNegativeCpc(): void
    {
        self::expectException(AdSelectRuntimeException::class);

        new Budget(100, -100, null);
    }

    public function testNegativeCpm(): void
    {
        self::expectException(AdSelectRuntimeException::class);

        new Budget(100, null, -100);
    }
}
