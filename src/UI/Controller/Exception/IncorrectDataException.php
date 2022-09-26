<?php

declare(strict_types=1);

namespace App\UI\Controller\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class IncorrectDataException extends BadRequestHttpException
{
    public function __construct(Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct('Incorrect data', $previous, $code, $headers);
    }
}
