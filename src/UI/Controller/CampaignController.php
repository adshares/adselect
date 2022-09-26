<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Dto\CampaignDeleteDto;
use App\Application\Dto\CampaignUpdateDto;
use App\Application\Dto\QueryDto;
use App\Application\Exception\ValidationDtoException;
use App\Application\Service\BannerFinder;
use App\Application\Service\CampaignUpdater;
use App\UI\Dto\FoundBannerResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CampaignController extends AbstractController
{
    private CampaignUpdater $campaignUpdater;
    private BannerFinder $bannerFinder;
    private LoggerInterface $logger;

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

        $results = [];
        foreach ($queries as $query) {
            $requestId = $query['request_id'] ?? uniqid('', true);

            try {
                $queryDto = QueryDto::fromArray($query);
                $banners = $this->bannerFinder->find($queryDto);
                $results[$requestId] = (new FoundBannerResponse($banners))->toArray();
            } catch (ValidationDtoException $exception) {
                $results[$requestId] = [];

                $this->logger->info(
                    sprintf('[Find] Invalid input data (%s).', $exception->getMessage()),
                    $query
                );
            }
        }

        return new JsonResponse($results, Response::HTTP_OK);
    }
}
