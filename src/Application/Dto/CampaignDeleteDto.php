<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Application\Exception\ValidationDtoException;
use App\Domain\Exception\AdSelectRuntimeException;
use App\Domain\Model\IdCollection;
use App\Domain\ValueObject\Id;

final class CampaignDeleteDto
{
    private IdCollection $ids;

    public function __construct(array $data)
    {
        $collection = new IdCollection();
        foreach ($data as $item) {
            try {
                $id = new Id($item);
            } catch (AdSelectRuntimeException $exception) {
                throw new ValidationDtoException($exception->getMessage());
            }

            if (!$collection->shouldBeAdded($id)) {
                $collection->add($id);
            }
        }

        $this->ids = $collection;
    }

    public function getIdCollection(): IdCollection
    {
        return $this->ids;
    }
}
