<?php

namespace App\Entity;

use App\Constant\OperationType;
use App\Entity\Constant\TransactionTable;
use App\Entity\Constant\WalletTable;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: TransactionTable::TABLE_NAME)]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    use TimestampableTrait;
    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    #[ORM\Id]
    #[ORM\Column(name: TransactionTable::COLUMN_ID, type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]

    private Uuid $id;

    #[ORM\Column(name: TransactionTable::COLUMN_AMOUNT, type: Types::DECIMAL, precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(name: TransactionTable::COLUMN_EXTERNAL_ID, type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalId;

    #[ORM\Column(name: TransactionTable::COLUMN_CREATED_AT, type: Types::DATETIME_IMMUTABLE, nullable: false)]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(name: TransactionTable::COLUMN_UPDATED_AT, type: Types::DATETIME_IMMUTABLE, nullable: false)]
    protected \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: TransactionTable::COLUMN_OPERATION_TYPE, type: Types::STRING, nullable: false, enumType: OperationType::class)]
    private OperationType $operationType;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: TransactionTable::COLUMN_WALLET_ID, referencedColumnName: WalletTable::COLUMN_ID, nullable: false)]
    private Wallet $wallet;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }


    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(Wallet $wallet): static
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getOperationType(): OperationType
    {
        return $this->operationType;
    }

    public function setOperationType(OperationType $operationType): static
    {
        $this->operationType = $operationType;

        return $this;
    }
}
