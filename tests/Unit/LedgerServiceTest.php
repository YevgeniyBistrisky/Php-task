<?php

namespace App\Tests\Unit;

use App\Entity\Ledger;
use App\Service\LedgerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class LedgerServiceTest extends TestCase
{
    public function testNewLedger(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $sut = new LedgerService($em);
        $result = $sut->newLedger();
        $wallet = $result->getWallets()->first();

        $this->assertInstanceOf(Ledger::class, $result);
        $this->assertInstanceOf(Uuid::class, $result->getId());
        $this->assertCount(1, $result->getWallets());
        $this->assertEquals(0, $wallet->getAmount());
        $this->assertEquals('UAH', $wallet->getCurrency()->value);
    }
}
