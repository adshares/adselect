<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\BannerCollection;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Domain\ValueObject\Uuid;
use Adshares\AdSelect\Lib\ExtendedDateTime;

final class CampaignUpdateDto
{
    private $campaigns;

    public function __construct(array $campaigns)
    {
        $this->validate($campaigns);

        $campaignCollection = new CampaignCollection();

        foreach ($campaigns as $campaign) {
            $campaignId = new Uuid($campaign['campaign_id']);
            $banners = $this->prepareBannerCollection($campaignId, $campaign['banners']);

            $campaign = new Campaign(
                $campaignId,
                ExtendedDateTime::createFromTimestamp($campaign['time_start']),
                ExtendedDateTime::createFromTimestamp($campaign['time_end']),
                $banners,
                $campaign['keywords'],
                $campaign['filters']
            );

            $campaignCollection->add($campaign);
        }

        $this->campaigns = $campaignCollection;
    }

    private function validate(array $campaigns): void
    {
        foreach ($campaigns as $campaign) {
            if (!isset($campaign['campaign_id'])) {
                throw new ValidationDtoException('Field `campaign_id` is required.');
            }

            if (!isset($campaign['banners'])) {
                throw new ValidationDtoException('Field `banners` is required.');
            }

            if (!isset($campaign['time_start'])) {
                throw new ValidationDtoException('Field `time_start` is required.');
            }

            if (!isset($campaign['keywords'])) {
                throw new ValidationDtoException('Field `keywords` is required.');
            }

            if (!isset($campaign['filters']['require'])) {
                throw new ValidationDtoException('Field `filters[require]` is required.');
            }

            if (!isset($campaign['filters']['exclude'])) {
                throw new ValidationDtoException('Field `filters[exclude]` is required.');
            }

            $this->validateBanners($campaign['banners']);
        }
    }

    private function validateBanners(array $banners): void
    {
        foreach ($banners as $banner) {
            if (!isset($banner['banner_id'])) {
                throw new ValidationDtoException('Field `banners[][banner_id]` is required.');
            }

            if (!isset($banner['banner_size'])) {
                throw new ValidationDtoException('Field `banners[][banner_size]` is required.');
            }

            if (!isset($banner['keywords'])) {
                throw new ValidationDtoException('Field `banners[][keywords]` is required.');
            }
        }
    }

    private function prepareBannerCollection(Uuid $campaignId, array $banners): BannerCollection
    {
        $collection = new BannerCollection();

        foreach ($banners as $banner) {
            $banner = new Banner(
                $campaignId,
                new Uuid($banner['banner_id']),
                $banner['banner_size'],
                $banner['keywords'] ?? []
            );

            $collection->add($banner);
        }

        return $collection;
    }

    public function getCampaignCollection(): CampaignCollection
    {
        return $this->campaigns;
    }
}
