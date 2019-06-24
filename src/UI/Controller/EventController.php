<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\UI\Controller;

use Adshares\AdSelect\Application\Dto\PaidEvents;
use Adshares\AdSelect\Application\Dto\UnpaidEvents;
use Adshares\AdSelect\Application\Exception\EventNotFound;
use Adshares\AdSelect\Application\Service\EventCollector;
use Adshares\AdSelect\Application\Service\EventFinder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function json_decode;
use function implode;
use function json_encode;
use function sprintf;

class EventController
{
    /** @var EventCollector */
    private $eventCollector;
    /** @var LoggerInterface */
    private $logger;
    /** @var EventFinder */
    private $eventFinder;

    public function __construct(EventCollector $eventCollector, EventFinder $eventFinder, LoggerInterface $logger)
    {
        $this->eventCollector = $eventCollector;
        $this->eventFinder = $eventFinder;
        $this->logger = $logger;
    }

    public function unpaidEvents(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        if ($content === null || !isset($content['events'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        $dto = new UnpaidEvents($content['events']);

        if (count($dto->events()) > 0) {
            $this->eventCollector->collect($dto->events());

            $this->logger->debug(
                sprintf(
                    '[%s] Events have been proceed (ids: %s).',
                    'COLLECT_UNPAID_EVENTS',
                    implode(', ', $dto->getEventsIds())
                )
            );
        }

        if (count($dto->failedEvents()) > 0) {
            $response = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Some events could not be proceed',
                'failed_events' => $dto->failedEvents(),
            ];

            $this->logger->debug(
                sprintf(
                    '[%s] Some events have not been proceed (%s)',
                    'COLLECT_UNPAID_EVENTS',
                    json_encode($dto->failedEvents())
                )
            );

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function paidEvents(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        if ($content === null || !isset($content['events'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        $dto = new PaidEvents($content['events']);

        if (count($dto->events()) > 0) {
            $this->eventCollector->collectPaidEvents($dto->events());

            $this->logger->debug(
                sprintf(
                    '[%s] Events have been proceed (ids: %s).',
                    'COLLECT_PAID_EVENTS',
                    implode(', ', $dto->getEventsIds())
                )
            );
        }

        if (count($dto->failedEvents()) > 0) {
            $this->logger->notice(
                sprintf(
                    '[%s] Some events have not been proceed (%s)',
                    'COLLECT_PAID_EVENTS',
                    json_encode($dto->failedEvents())
                )
            );
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function lastUnpaidEvent(): JsonResponse
    {
        try {
            $event = $this->eventFinder->findLastUnpaidEvent();
        } catch (EventNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
        return new JsonResponse($event->toArray(), Response::HTTP_OK);
    }

    public function lastPaidEvent(): JsonResponse
    {
        try {
            $event = $this->eventFinder->findLastPaidEvent();
        } catch (EventNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }


        return new JsonResponse($event->toArray(), Response::HTTP_OK);
    }
}
