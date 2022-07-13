<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Model\CampaignCollection;
use App\Domain\Model\IdCollection;

interface CampaignUpdater
{
    public function update(CampaignCollection $campaigns): void;

    public function delete(IdCollection $ids): void;
}
