<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\UI\Controller;

use Adshares\AdSelect\Application\Dto\UnpaidEvents;
use Adshares\AdSelect\Application\Service\EventCollector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function json_decode;

class EventController
{
    /** @var EventCollector */
    private $eventCollector;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(EventCollector $eventCollector, LoggerInterface $logger)
    {
        $this->eventCollector = $eventCollector;
        $this->logger = $logger;
    }

    public function unpaidEvents(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        if ($content === null || !isset($content['events'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        $dto = new UnpaidEvents($content['events']);

        if (count($dto->failedEvents()) > 0) {
            // some logs should be added here
        }

        $this->eventCollector->collect($dto->events());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

}
