<?php

declare(strict_types=1);


namespace Adshares\AdSelect\UI\Controller;

use Adshares\AdSelect\Application\Dto\CampaignUpdateDto;
use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function json_decode;

class CampaignController
{
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

        // service->update($dto->getCampaignCollection());

//        $campaigns = $dto->getCampaignCollection();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
