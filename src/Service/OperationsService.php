<?php

namespace App\Service;

use App\Constant\OperationType;
use App\Dto\Request\OperationRequestDto;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Exception\Business\InsufficientBalanceException;
use App\Exception\Business\TooManyRequestsException;
use App\Exception\Business\WalletNotFoundException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

class OperationsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function processOperationRequest(OperationRequestDto $requestDto): void
    {
        $this->entityManager->beginTransaction();
        try {
            $wallet = $this->entityManager->getRepository(Wallet::class)
                ->findOneBy([
                    'ledger' => $requestDto->ledgerId,
                    'currency' => $requestDto->currency->value,
                ]);
            if (!$wallet) {
                throw  WalletNotFoundException::walletNotFound($requestDto->ledgerId, $requestDto->currency);
            }
            $this->entityManager->lock($wallet, LockMode::OPTIMISTIC);

            $currentBalance = $wallet->getBalance();
            $amountChange = Money::of(
                $requestDto->amount,
                $requestDto->currency->value,
                roundingMode: RoundingMode::HALF_DOWN
            );
            $newBalance = $this->calculateNewBalance($requestDto->operationType, $currentBalance, $amountChange);
            $wallet->setBalance($newBalance);
            $transaction = new Transaction();
            $transaction->setAmount((float) $requestDto->amount)
                ->setOperationType($requestDto->operationType)
                ->setExternalId($requestDto->transactionId);
            $wallet->addTransaction($transaction);
            $this->entityManager->persist($wallet);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (OptimisticLockException) {
            throw TooManyRequestsException::tooManyUpdateRequests();
        } catch (\Exception $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            throw $e;
        }
    }

    private function calculateNewBalance(OperationType $operationType, Money $currentBalance, Money $amountChange): Money
    {
        if ($operationType === OperationType::CREDIT) {
            $this->assertBalanceSufficient($currentBalance, $amountChange);
        }

        return match ($operationType) {
            OperationType::CREDIT => $currentBalance->minus($amountChange),
            OperationType::DEBIT => $currentBalance->plus($amountChange),
        };
    }

    private function assertBalanceSufficient(Money $currentBalance, Money $amountChange): void
    {
        if ($currentBalance->isLessThan($amountChange)) {
            throw new InsufficientBalanceException();
        }
    }
}
