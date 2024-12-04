<?php

namespace App\Entity;

use App\Entity\Constant\Currency;
use App\Entity\Constant\WalletTable;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\WalletRepository;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
#[ORM\UniqueConstraint(name: 'ledger_currency', columns: ['ledger_id', 'currency'])]
#[ORM\Table(name: WalletTable::TABLE_NAME)]
#[ORM\HasLifecycleCallbacks]
class Wallet
{
    use TimestampableTrait;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->version = 1;
        $this->amount = 0;
        $this->transactions = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\Column(name: WalletTable::COLUMN_ID, type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Version]
    #[ORM\Column(name: WalletTable::COLUMN_VERSION, type: Types::INTEGER, nullable: false)]
    private int $version;

    #[ORM\ManyToOne(inversedBy: 'wallets')]
    #[ORM\JoinColumn(nullable: false)]
    private Ledger $ledger;

    #[ORM\Column(type: Types::STRING, length: 3, nullable: false, enumType: Currency::class)]
    #[Groups('ledger:read')]
    private Currency $currency;

    #[ORM\Column(name: WalletTable::COLUMN_CREATED_AT, type: Types::DATETIME_IMMUTABLE, nullable: false)]
    #[Groups('ledger:read')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(name: WalletTable::COLUMN_UPDATED_AT, type: Types::DATETIME_IMMUTABLE, nullable: false)]
    #[Groups('ledger:read')]
    protected \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: WalletTable::COLUMN_AMOUNT, type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups('ledger:read')]
    private float $amount;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'wallet', orphanRemoval: true)]
    private Collection $transactions;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function getLedger(): Ledger
    {
        return $this->ledger;
    }

    public function setLedger(Ledger $ledger): static
    {
        $this->ledger = $ledger;

        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBalance(): Money
    {
        return Money::of(amount: $this->amount, currency: $this->currency->value, roundingMode: RoundingMode::HALF_DOWN);
    }

    public function setBalance(Money $balance): static
    {
        if ($balance->getCurrency()->getCurrencyCode() !== $this->currency->value) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        if ($balance->getAmount()->isNegative()) {
            throw new \InvalidArgumentException('Balance cannot be negative');
        }

        $this->amount = $balance->getAmount()->toFloat();

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setWallet($this);
        }

        return $this;
    }
}
