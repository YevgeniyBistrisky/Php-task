<?php

namespace App\Exception\Business;

use Symfony\Component\HttpFoundation\Response;

class TooManyRequestsException extends BusinessException
{
    public static function tooManyUpdateRequests(): self
    {
        return new self('Too many requests for same balance update', Response::HTTP_TOO_MANY_REQUESTS);
    }
}
