<?php

declare(strict_types=1);

namespace App\Tests\Action;

use App\Entity\Constant\Currency;
use App\Entity\Ledger;
use App\Repository\LedgerRepository;
use App\Service\LedgerService;
use App\Tests\Factory\LedgerFactory;
use App\Tests\Factory\WalletFactory;
use Brick\Money\Money;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\ResetDatabase;

class OperationTest extends WebTestCase
{
    use ResetDatabase;

    private function prepareLedger(): Ledger
    {
        return $this->getContainer()->get(LedgerService::class)->newLedger();
    }

    /**
     * @dataProvider successOperationProvider
     */
    public function testSuccessOperation(array $operations, float $expectedBalance): void
    {
        $client = static::createClient();
        $ledger = $this->prepareLedger();
        $ledgerId = $ledger->getId()->toRfc4122();

        foreach ($operations as $operation) {
            $client->jsonRequest(
                Request::METHOD_POST,
                '/transactions',
                [
                    'ledgerId' => $ledger->getId(),
                    'operationType' => $operation['operationType'],
                    'amount' => $operation['amount'],
                    'currency' => $operation['currency'],
                    'transactionId' => $operation['transactionId'],
                ]
            );
            $this->assertResponseIsSuccessful();
        }
        $ledger = $this->getContainer()->get(LedgerRepository::class)->find($ledgerId);
        $wallet = $ledger->getWallets()->first();

        $this->assertEquals($expectedBalance, $wallet->getBalance()->getAmount()->toFloat());
        $this->assertCount(count($operations), $wallet->getTransactions());
    }

    private function successOperationProvider(): iterable
    {
        yield [
            [
              [
                  'operationType' => 'debit',
                  'amount' => 100,
                  'currency' => 'UAH',
                  'transactionId' => Uuid::uuid(),
              ],
            ],
            100,
        ];
        yield [
            [
                [
                    'operationType' => 'debit',
                    'amount' => 1000,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 50,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'debit',
                    'amount' => 10.50,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'debit',
                    'amount' => 0.25,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 12.30,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
            ],
            948.45,
        ];
        yield [
            [
                [
                    'operationType' => 'debit',
                    'amount' => 1000,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 500,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'debit',
                    'amount' => 0.50,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'debit',
                    'amount' => 0.25,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 11.14,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 18.18,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
            ],
            471.43,
        ];
        yield [
            [
                [
                    'operationType' => 'debit',
                    'amount' => 100.28,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 50.12,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
                [
                    'operationType' => 'credit',
                    'amount' => 50.16,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
            ],
            0,
        ];
    }

    /**
     * @dataProvider unsuccessfulOperationsProvider
     */
    public function testNotSuccessfulOperation(array $operation, int $responseCode, string $expectedMessage): void
    {
        $client = static::createClient();
        $ledger = $this->prepareLedger();
        $wallet = $ledger->getWallets()->first();
        $wallet->setBalance(Money::of('1000', 'UAH'));
        $this->getContainer()->get(EntityManagerInterface::class)->persist($wallet);
        $this->getContainer()->get(EntityManagerInterface::class)->flush();

        $client->jsonRequest(
            Request::METHOD_POST,
            '/transactions',
            [
                'ledgerId' => $ledger->getId(),
                'operationType' => $operation['operationType'],
                'amount' => $operation['amount'],
                'currency' => $operation['currency'],
                'transactionId' => $operation['transactionId'],
            ]
        );
        $response = $client->getResponse();
        $responseBody = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame($responseCode);
        $this->assertEquals($expectedMessage, $responseBody['message']);

    }

    private function unsuccessfulOperationsProvider(): iterable
    {
        // #0 Negative amount
        yield [
                [
                    'operationType' => 'debit',
                    'amount' => -100,
                    'currency' => 'UAH',
                    'transactionId' => Uuid::uuid(),
                ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'amount: This value should be positive.',
        ];
        // #1 Zero amount
        yield [
            [
                'operationType' => 'debit',
                'amount' => 0,
                'currency' => 'UAH',
                'transactionId' => Uuid::uuid(),
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'amount: This value should be positive.',
        ];
        // #2 Unknown operation type
        yield [
            [
                'operationType' => 'none',
                'amount' => 100,
                'currency' => 'UAH',
                'transactionId' => Uuid::uuid(),
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'operationType: The value you selected is not a valid choice.',
        ];
        // #3 Unknown operation type
        yield [
            [
                'operationType' => 'debit',
                'amount' => 100,
                'currency' => 'USO',
                'transactionId' => Uuid::uuid(),
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'currency: The value you selected is not a valid choice.',
        ];
        // #4 Nullable data
        yield [
            [
                'operationType' => null,
                'amount' => null,
                'currency' => null,
                'transactionId' => Uuid::uuid(),
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'amount: This value should not be blank.; currency: This value should not be blank.; operationType: This value should not be blank.',
        ];
    }
}
