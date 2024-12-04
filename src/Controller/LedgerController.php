<?php

namespace App\Controller;

use App\Entity\Ledger;
use App\Service\LedgerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LedgerController extends AbstractController
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {
    }

    #[Route('/ledgers', name: 'app_ledger_create', methods: ['POST'])]
    public function index(): JsonResponse
    {
        $ledger = $this->ledgerService->newLedger();

        return $this->json(
            $ledger,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'ledger:read'],
        );
    }

    #[Route('/balance/{id}', name: 'app_ledger_balance', methods: ['GET'])]
    public function balance(Ledger $ledger): JsonResponse
    {
        return $this->json(
            $ledger->getWallets(),
            Response::HTTP_OK,
            [],
            ['groups' => 'ledger:read'],
        );
    }
}
