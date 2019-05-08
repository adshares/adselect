<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\UI\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionListenerSubscriber implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if (!$exception instanceof HttpException) {
            return;
        }

        $data = [
            'code' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
        ];

        $response = new JsonResponse($data, $exception->getStatusCode());

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }
}
