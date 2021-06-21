<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Dto;

use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;
use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\BannerCollection;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Domain\ValueObject\Budget;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Domain\ValueObject\Size;
use Adshares\AdSelect\Lib\Exception\LibraryRuntimeException;
use Adshares\AdSelect\Lib\ExtendedDateTime;

final class CampaignUpdateDto
{
    private $campaigns;

    public function __construct(array $campaigns)
    {
        $this->validate($campaigns);

        $campaignCollection = new CampaignCollection();

        foreach ($campaigns as $campaign) {
            $campaignCollection->add($this->createCampaignModel($campaign));
        }

        $this->campaigns = $campaignCollection;
    }

    private function createCampaignModel(array $campaignData): Campaign
    {
        try {
            $campaignId = new Id($campaignData['campaign_id']);
            $banners = $this->prepareBannerCollection($campaignId, $campaignData['banners']);
            $budget = new Budget(
                $campaignData['budget'],
                $campaignData['max_cpc'] ?? null,
                $campaignData['max_cpm'] ?? null
            );

            return new Campaign(
                $campaignId,
                ExtendedDateTime::createFromTimestamp($campaignData['time_start']),
                $campaignData['time_end'] ? ExtendedDateTime::createFromTimestamp($campaignData['time_end']) : null,
                $banners,
                $campaignData['keywords'],
                $campaignData['filters'],
                $budget
            );
        } catch (AdSelectRuntimeException | LibraryRuntimeException $exception) {
            throw new ValidationDtoException($exception->getMessage());
        }
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

            if (!isset($campaign['budget'])) {
                throw new ValidationDtoException('Field `budget` is required.');
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

    private function prepareBannerCollection(Id $campaignId, array $banners): BannerCollection
    {
        $collection = new BannerCollection();

        foreach ($banners as $banner) {
            $banner = new Banner(
                $campaignId,
                new Id($banner['banner_id']),
                new Size($banner['banner_size']),
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
