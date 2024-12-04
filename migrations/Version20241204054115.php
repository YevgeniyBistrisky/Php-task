<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204054115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ledgers (id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE transactions (id UUID NOT NULL, amount NUMERIC(10, 2) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, operation_type VARCHAR(255) NOT NULL, wallet_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EAA81A4C712520F3 ON transactions (wallet_id)');
        $this->addSql('CREATE TABLE wallets (id UUID NOT NULL, version INT DEFAULT 1 NOT NULL, currency VARCHAR(3) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount NUMERIC(10, 2) NOT NULL, ledger_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_967AAA6CA7B913DD ON wallets (ledger_id)');
        $this->addSql('CREATE UNIQUE INDEX ledger_currency ON wallets (ledger_id, currency)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C712520F3 FOREIGN KEY (wallet_id) REFERENCES wallets (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wallets ADD CONSTRAINT FK_967AAA6CA7B913DD FOREIGN KEY (ledger_id) REFERENCES ledgers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transactions DROP CONSTRAINT FK_EAA81A4C712520F3');
        $this->addSql('ALTER TABLE wallets DROP CONSTRAINT FK_967AAA6CA7B913DD');
        $this->addSql('DROP TABLE ledgers');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE wallets');
    }
}
