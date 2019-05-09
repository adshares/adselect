<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\UI\EventListener;

use function in_array;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionListenerSubscriber implements EventSubscriberInterface
{
    /** @var string */
    private $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if (!$exception instanceof HttpException) {
            $data = [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Internal error',
            ];

            if (in_array($this->env, ['dev', 'test'])) {
                $previous = $exception->getPrevious();

                $data['originalMessage'] = $exception->getMessage();
                $data['trace'] = $exception->getTrace();

                if ($previous) {
                    $data['previous'] = [
                        'message' => $previous->getMessage(),
                        'trace' => $previous->getTrace(),
                    ];
                }
            }

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
