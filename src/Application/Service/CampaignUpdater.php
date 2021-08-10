<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Service;

use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Domain\Model\IdCollection;

interface CampaignUpdater
{
    public function update(CampaignCollection $campaigns): void;

    public function delete(IdCollection $ids): void;
}
