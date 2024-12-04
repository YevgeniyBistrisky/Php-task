<?php

namespace App\Exception\Business;

use App\Entity\Constant\Currency;
use Symfony\Component\HttpFoundation\Response;

class InsufficientBalanceException extends BusinessException
{
    public static function walletNotFound(): self
    {
        return new self('Insufficient balance', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
