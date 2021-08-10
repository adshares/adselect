<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionListenerSubscriber implements EventSubscriberInterface
{
    private string $env;
    private LoggerInterface $logger;

    public function __construct(string $env, LoggerInterface $logger)
    {
        $this->env = $env;
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpException) {
            $data = [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Internal error',
            ];

            $additional = [];
            $previous = $exception->getPrevious();

            $additional['originalMessage'] = $exception->getMessage();
            $additional['trace'] = $exception->getTrace();

            if ($previous) {
                $additional['previous'] = [
                    'message' => $previous->getMessage(),
                    'trace' => $previous->getTrace(),
                ];
            }

            if (in_array($this->env, ['dev', 'test'])) {
                $data = array_merge($data, $additional);
            }

            $this->logger->error($exception->getMessage(), $additional);
            $response = new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $data = [
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ];

            $response = new JsonResponse($data, $exception->getStatusCode());
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }
}
