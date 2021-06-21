<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Application\Dto;

use Adshares\AdSelect\Application\Dto\CampaignDeleteDto;
use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use PHPUnit\Framework\TestCase;

final class CampaignDeleteDtoTest extends TestCase
{
    public function testWhenIdsAreCorrectAndDuplicate(): void
    {
        $ids = [
            '00000000000000000000000000000001',
            '00000000000000000000000000000002',
            '00000000000000000000000000000002',
            '00000000000000000000000000000002',
            '00000000000000000000000000000001',
            '00000000000000000000000000000003',
        ];

        $dto = new CampaignDeleteDto($ids);

        $collection = $dto->getIdCollection();

        $this->assertCount(3, $collection);
        $this->assertEquals('00000000000000000000000000000001', $collection[0]);
        $this->assertEquals('00000000000000000000000000000002', $collection[1]);
        $this->assertEquals('00000000000000000000000000000003', $collection[2]);
    }

    public function testWhenIdIsNotCorrect(): void
    {
        $this->expectException(ValidationDtoException::class);

        $ids = [
            '11111111',
            '22222222',
        ];

        new CampaignDeleteDto($ids);
    }
}
