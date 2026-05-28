<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520171417 extends AbstractMigration
{
	public function getDescription(): string
	{
		return 'Create table for factions newspaper';
	}

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE communication__news (id BINARY(16) NOT NULL, type VARCHAR(96) NOT NULL, created_at DATETIME NOT NULL, data JSON NOT NULL, faction_id BINARY(16) DEFAULT NULL, INDEX IDX_C17728FE4448F8DA (faction_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('ALTER TABLE communication__news ADD CONSTRAINT FK_C17728FE4448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE communication__news DROP FOREIGN KEY FK_C17728FE4448F8DA');
		$this->addSql('DROP TABLE communication__news');
	}
}
