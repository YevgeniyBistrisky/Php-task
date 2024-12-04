<?php

namespace App\Service;

use App\Entity\Constant\Currency;
use App\Entity\Ledger;
use App\Entity\Wallet;
use Doctrine\ORM\EntityManagerInterface;

class LedgerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {

    }

    public function newLedger(): Ledger
    {
        $wallet = (new Wallet())->setCurrency(Currency::UAH);
        $ledger = (new Ledger())->addWallet($wallet);
        $this->entityManager->persist($ledger);
        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        return $ledger;
    }
}
