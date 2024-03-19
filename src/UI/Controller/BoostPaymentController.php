<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Dto\BoostPayments;
use App\Application\Exception\BoostPaymentNotFound;
use App\Application\Service\BoostPaymentCollector;
use App\Application\Service\BoostPaymentFinder;
use App\UI\Controller\Exception\IncorrectDataException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BoostPaymentController extends AbstractController
{
    private BoostPaymentCollector $boostPaymentCollector;
    private BoostPaymentFinder $boostPaymentFinder;
    private LoggerInterface $logger;

    public function __construct(
        BoostPaymentCollector $boostPaymentCollector,
        BoostPaymentFinder $boostPaymentFinder,
        LoggerInterface $logger
    ) {
        $this->boostPaymentCollector = $boostPaymentCollector;
        $this->boostPaymentFinder = $boostPaymentFinder;
        $this->logger = $logger;
    }

    public function lastPayment(): JsonResponse
    {
        try {
            $payment = $this->boostPaymentFinder->findLastPayment();
        } catch (BoostPaymentNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return new JsonResponse($payment->toArray(), Response::HTTP_OK);
    }

    public function newPayments(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if (null === $content || !isset($content['payments'])) {
            throw new IncorrectDataException();
        }

        $dto = new BoostPayments($content['payments']);

        if (!$dto->payments()->isEmpty()) {
            $this->boostPaymentCollector->collectPayments($dto->payments());

            $this->logger->debug(
                sprintf(
                    '[BOOST PAYMENTS] Payments have been proceed (ids: %s).',
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
                    '[BOOST PAYMENTS] Some payments have not been proceed (%s)',
                    json_encode($dto->failedPayments())
                )
            );

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
