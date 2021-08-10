<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Controller;

use Adshares\AdSelect\Application\Dto\Cases;
use Adshares\AdSelect\Application\Dto\Clicks;
use Adshares\AdSelect\Application\Dto\Payments;
use Adshares\AdSelect\Application\Exception\EventNotFound;
use Adshares\AdSelect\Application\Service\EventCollector;
use Adshares\AdSelect\Application\Service\EventFinder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function lastCase(): JsonResponse
    {
        try {
            $event = $this->eventFinder->findLastCase();
        } catch (EventNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return new JsonResponse($event->toArray(), Response::HTTP_OK);
    }

    public function lastClick(): JsonResponse
    {
        try {
            $event = $this->eventFinder->findLastCLick();
        } catch (EventNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return new JsonResponse($event->toArray(), Response::HTTP_OK);
    }

    public function lastPayment(): JsonResponse
    {
        try {
            $event = $this->eventFinder->findLastPayment();
        } catch (EventNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return new JsonResponse($event->toArray(), Response::HTTP_OK);
    }

    public function newCases(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if ($content === null || !isset($content['cases'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        $dto = new Cases($content['cases']);

        if (count($dto->events()) > 0) {
            $this->eventCollector->collectCases($dto->events());

            $this->logger->debug(
                sprintf(
                    '[%s] Cases have been proceed (ids: %s).',
                    'COLLECT_CASES',
                    implode(', ', $dto->getEventsIds())
                )
            );
        }

        if (count($dto->failedEvents()) > 0) {
            $response = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Some cases could not be proceed',
                'failed_events' => $dto->failedEvents(),
            ];

            $this->logger->debug(
                sprintf(
                    '[%s] Some cases have not been proceed (%s)',
                    'COLLECT_CASES',
                    json_encode($dto->failedEvents())
                )
            );

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function newClicks(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if ($content === null || !isset($content['clicks'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        $dto = new Clicks($content['clicks']);

        if (count($dto->events()) > 0) {
            $this->eventCollector->collectClicks($dto->events());

            $this->logger->debug(
                sprintf(
                    '[%s] Clicks have been proceed (ids: %s).',
                    'COLLECT_CLICKS',
                    implode(', ', $dto->getEventsIds())
                )
            );
        }

        if (count($dto->failedEvents()) > 0) {
            $response = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Some clicks could not be proceed',
                'failed_events' => $dto->failedEvents(),
            ];

            $this->logger->debug(
                sprintf(
                    '[%s] Some clicks have not been proceed (%s)',
                    'COLLECT_CLICKS',
                    json_encode($dto->failedEvents())
                )
            );

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function newPayments(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if ($content === null || !isset($content['payments'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        $dto = new Payments($content['payments']);

        if (count($dto->events()) > 0) {
            $this->eventCollector->collectPayments($dto->events());

            $this->logger->debug(
                sprintf(
                    '[%s] Payments have been proceed (ids: %s).',
                    'COLLECT_PAYMENTS',
                    implode(', ', $dto->getEventsIds())
                )
            );
        }

        if (count($dto->failedEvents()) > 0) {
            $response = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Some payments could not be proceed',
                'failed_events' => $dto->failedEvents(),
            ];

            $this->logger->debug(
                sprintf(
                    '[%s] Some payments have not been proceed (%s)',
                    'COLLECT_PAYMENTS',
                    json_encode($dto->failedEvents())
                )
            );

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
