<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Dto\ExperimentPayments;
use App\Application\Exception\ExperimentPaymentNotFound;
use App\Application\Service\ExperimentPaymentCollector;
use App\Application\Service\ExperimentPaymentFinder;
use App\UI\Controller\Exception\IncorrectDataException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExperimentPaymentController extends AbstractController
{
    private ExperimentPaymentCollector $experimentPaymentCollector;
    private ExperimentPaymentFinder $experimentPaymentFinder;
    private LoggerInterface $logger;

    public function __construct(
        ExperimentPaymentCollector $experimentPaymentCollector,
        ExperimentPaymentFinder $experimentPaymentFinder,
        LoggerInterface $logger
    ) {
        $this->experimentPaymentCollector = $experimentPaymentCollector;
        $this->experimentPaymentFinder = $experimentPaymentFinder;
        $this->logger = $logger;
    }

    public function lastPayment(): JsonResponse
    {
        try {
            $experimentPayment = $this->experimentPaymentFinder->findLastPayment();
        } catch (ExperimentPaymentNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return new JsonResponse($experimentPayment->toArray(), Response::HTTP_OK);
    }

    public function newPayments(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if (null === $content || !isset($content['payments'])) {
            throw new IncorrectDataException();
        }

        $dto = new ExperimentPayments($content['payments']);

        if (!$dto->payments()->isEmpty()) {
            $this->experimentPaymentCollector->collectPayments($dto->payments());

            $this->logger->debug(
                sprintf(
                    '[EXPERIMENT PAYMENTS] Payments have been proceed (ids: %s).',
                    implode(', ', $dto->getPaymentIds())
                )
            );
        }

        if (count($dto->failedPayments()) > 0) {
            $response = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Some payments could not be proceed',
                'failed_events' => $dto->failedPayments(),
            ];

            $this->logger->debug(
                sprintf(
                    '[EXPERIMENT PAYMENTS] Some payments have not been proceed (%s)',
                    json_encode($dto->failedPayments())
                )
            );

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
