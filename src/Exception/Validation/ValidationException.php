<?php

namespace App\Exception\Validation;

use Symfony\Component\HttpFoundation\Response;

class ValidationException extends \RuntimeException
{
    public static function operationValidationFailed(string $errorMessage): self
    {
        return new self($errorMessage, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
