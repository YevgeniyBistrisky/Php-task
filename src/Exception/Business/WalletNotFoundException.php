<?php

namespace App\Exception\Business;

use App\Entity\Constant\Currency;
use Symfony\Component\HttpFoundation\Response;

class WalletNotFoundException extends BusinessException
{
    public static function walletNotFound(string $ledgerId, Currency $currency): self
    {
        return new self(sprintf('Wallet for ledger "%s" and currency "%s" not found', $ledgerId, $currency->value), Response::HTTP_NOT_FOUND);
    }
}
