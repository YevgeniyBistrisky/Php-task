<?php

namespace App\Entity;

use App\Entity\Constant\Currency;
use App\Entity\Constant\LedgerTable;
use App\Repository\LedgerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LedgerRepository::class)]
#[ORM\Table(name: LedgerTable::TABLE_NAME)]
class Ledger
{
    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->wallets = new ArrayCollection();
    }


    #[ORM\Id]
    #[ORM\Column(name: LedgerTable::COLUMN_ID, type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups('ledger:read')]
    private Uuid $id;

    /**
     * @var Collection<int, Wallet>
     */
    #[ORM\OneToMany(targetEntity: Wallet::class, mappedBy: 'ledger', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[Groups('ledger:read')]
    private Collection $wallets;

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Wallet>
     */
    public function getWallets(): Collection
    {
        return $this->wallets;
    }

    public function addWallet(Wallet $wallet): static
    {
        if (!$this->wallets->contains($wallet)) {
            $this->wallets->add($wallet);
            $wallet->setLedger($this);
        }

        return $this;
    }
}
