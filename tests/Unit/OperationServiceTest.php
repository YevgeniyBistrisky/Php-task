<?php

namespace App\Tests\Unit;

use App\Constant\OperationType;
use App\Dto\Request\OperationRequestDto;
use App\Entity\Constant\Currency;
use App\Entity\Wallet;
use App\Exception\Business\TooManyRequestsException;
use App\Exception\Business\WalletNotFoundException;
use App\Repository\WalletRepository;
use App\Service\OperationsService;
use Brick\Money\Money;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OperationServiceTest extends TestCase
{
    public function testWalletNotFound(): void
    {
        $repoMock = $this->createMock(WalletRepository::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->once())->method('getRepository')->willReturn($repoMock);
        $dto = new OperationRequestDto(
            amount: 100,
            currency: Currency::USD,
            operationType: OperationType::DEBIT,
            transactionId: 'transactionId',
            ledgerId: 'ledgerId'
        );

        $sut = new OperationsService($emMock);

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet for ledger "ledgerId" and currency "USD" not found');
        $sut->processOperationRequest($dto);
    }

    public function testSuccessfulDebitOperation(): void
    {
        $repoMock = $this->createMock(WalletRepository::class);
        $wallet = new Wallet();
        $wallet->setCurrency(Currency::USD);
        $wallet->setBalance(Money::of(200, 'USD'));
        $repoMock->method('findOneBy')
            ->with([
                'ledger' => 'ledgerId',
                'currency' => 'USD',
            ])
            ->willReturn($wallet);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);
        $dto = new OperationRequestDto(
            amount: 100,
            currency: Currency::USD,
            operationType: OperationType::DEBIT,
            transactionId: 'transactionId',
            ledgerId: 'ledgerId'
        );

        $sut = new OperationsService($emMock);
        $sut->processOperationRequest($dto);
        $transaction = $wallet->getTransactions()->first();

        $this->assertEquals(300, $wallet->getAmount());
        $this->assertCount(1, $wallet->getTransactions());
        $this->assertEquals(OperationType::DEBIT, $transaction->getOperationType());
        $this->assertEquals(100, $transaction->getAmount());
        $this->assertEquals('transactionId', $transaction->getExternalId());
    }


    public function testSuccessfulCreditOperation(): void
    {
        $repoMock = $this->createMock(WalletRepository::class);
        $wallet = new Wallet();
        $wallet->setCurrency(Currency::USD);
        $wallet->setBalance(Money::of(200, 'USD'));
        $repoMock->method('findOneBy')
            ->with([
                'ledger' => 'ledgerId',
                'currency' => 'USD',
            ])
            ->willReturn($wallet);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);
        $dto = new OperationRequestDto(
            amount: 100,
            currency: Currency::USD,
            operationType: OperationType::CREDIT,
            transactionId: 'transactionId',
            ledgerId: 'ledgerId'
        );

        $sut = new OperationsService($emMock);
        $sut->processOperationRequest($dto);
        $transaction = $wallet->getTransactions()->first();

        $this->assertEquals(100, $wallet->getAmount());
        $this->assertCount(1, $wallet->getTransactions());
        $this->assertEquals(OperationType::CREDIT, $transaction->getOperationType());
        $this->assertEquals(100, $transaction->getAmount());
        $this->assertEquals('transactionId', $transaction->getExternalId());
    }

    public function testLockTriggered(): void
    {
        $repoMock = $this->createMock(WalletRepository::class);
        $wallet = new Wallet();
        $wallet->setCurrency(Currency::USD);
        $wallet->setBalance(Money::of(200, 'USD'));
        $repoMock->method('findOneBy')
            ->with([
                'ledger' => 'ledgerId',
                'currency' => 'USD',
            ])
            ->willReturn($wallet);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);
        $emMock->expects($this->once())->method('lock');
        $emMock->expects($this->once())->method('flush')->willThrowException(TooManyRequestsException::tooManyUpdateRequests());
        $dto = new OperationRequestDto(
            amount: 100,
            currency: Currency::USD,
            operationType: OperationType::CREDIT,
            transactionId: 'transactionId',
            ledgerId: 'ledgerId'
        );

        $sut = new OperationsService($emMock);

        $this->expectException(TooManyRequestsException::class);
        $sut->processOperationRequest($dto);
    }

    public function testErrorTriggered(): void
    {
        $repoMock = $this->createMock(WalletRepository::class);
        $wallet = new Wallet();
        $wallet->setCurrency(Currency::USD);
        $wallet->setBalance(Money::of(200, 'USD'));
        $repoMock->method('findOneBy')
            ->with([
                'ledger' => 'ledgerId',
                'currency' => 'USD',
            ])
            ->willReturn($wallet);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);
        $emMock->expects($this->once())->method('lock');
        $emMock->expects($this->once())->method('persist')->willThrowException(new \Exception('some error'));
        $dto = new OperationRequestDto(
            amount: 100,
            currency: Currency::USD,
            operationType: OperationType::CREDIT,
            transactionId: 'transactionId',
            ledgerId: 'ledgerId'
        );

        $sut = new OperationsService($emMock);

        $this->expectException(\Exception::class);
        $sut->processOperationRequest($dto);
    }
}
