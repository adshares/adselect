<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Service;

use Adshares\AdSelect\Domain\Model\CampaignCollection;

interface CampaignUpdater
{
    public function update(CampaignCollection $campaigns): void;
}
