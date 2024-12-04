<?php

declare(strict_types=1);

namespace App\Tests\Action;

use App\Entity\Ledger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\ResetDatabase;

class CreateLedgerTest extends WebTestCase
{
    use ResetDatabase;

    public function testCreate(): void
    {
        $client = static::createClient();

        $client->jsonRequest(
            Request::METHOD_POST,
            '/ledgers',
        );
        $response = $client->getResponse();
        $responseBody = json_decode($response->getContent(), true);
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var Ledger $ledger */
        $ledger = $em->getRepository(Ledger::class)->findOneBy(['id' => $responseBody['id']]);
        $wallet = $ledger->getWallets()->first();


        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertNotNull($ledger);
        $this->assertEquals($ledger->getWallets()->count(), 1);
        $this->assertEquals($wallet->getAmount(), 0);
        $this->assertEquals($wallet->getCurrency()->value, 'UAH');
    }
}
