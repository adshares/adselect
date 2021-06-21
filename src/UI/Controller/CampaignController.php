<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Controller;

use Adshares\AdSelect\Application\Dto\CampaignDeleteDto;
use Adshares\AdSelect\Application\Dto\CampaignUpdateDto;
use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Adshares\AdSelect\Application\Service\BannerFinder;
use Adshares\AdSelect\Application\Service\CampaignUpdater;
use Adshares\AdSelect\UI\Dto\FoundBannerResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CampaignController
{
    /** @var CampaignUpdater */
    private $campaignUpdater;
    /** @var BannerFinder */
    private $bannerFinder;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(CampaignUpdater $campaignUpdater, BannerFinder $bannerFinder, LoggerInterface $logger)
    {
        $this->campaignUpdater = $campaignUpdater;
        $this->bannerFinder = $bannerFinder;
        $this->logger = $logger;
    }

    public function update(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        if ($content === null || !isset($content['campaigns'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        try {
            $dto = new CampaignUpdateDto($content['campaigns']);
        } catch (ValidationDtoException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        $this->campaignUpdater->update($dto->getCampaignCollection());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function delete(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        if ($content === null || !isset($content['campaigns'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        try {
            $dto = new CampaignDeleteDto($content['campaigns']);
        } catch (ValidationDtoException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        $this->campaignUpdater->delete($dto->getIdCollection());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function findBanners(Request $request): JsonResponse
    {
        $queries = json_decode($request->getContent(), true);
        $size = 1;

        $results = [];
        foreach ($queries as $query) {
            $requestId = $query['request_id'] ?? uniqid('', true);

            try {
                $queryDto = QueryDto::fromArray($query);
                $banners = $this->bannerFinder->find($queryDto, $size);
                $results[$requestId] = (new FoundBannerResponse($banners))->toArray();
            } catch (ValidationDtoException $exception) {
                $results[$requestId] = [];

                $this->logger->info(
                    sprintf('[Find] Invalid input data (%s).', $exception->getMessage()),
                    $query
                );
                // think about adding a referer and more data related to a server which asks
            }
        }

        return new JsonResponse($results, Response::HTTP_OK);
    }
}
