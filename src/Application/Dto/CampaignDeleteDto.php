<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\Model\IdCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;

final class CampaignDeleteDto
{
    /** @var IdCollection */
    private $ids;

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
