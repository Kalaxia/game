<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526235536 extends AbstractMigration
{
	public function getDescription(): string
	{
		return 'Add source relation to factions for News table';
	}

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE communication__news DROP FOREIGN KEY `FK_C17728FE4448F8DA`');
		$this->addSql('DROP INDEX IDX_C17728FE4448F8DA ON communication__news');
		$this->addSql('ALTER TABLE communication__news ADD recipient_id BINARY(16) DEFAULT NULL, CHANGE faction_id source_id BINARY(16) DEFAULT NULL');
		$this->addSql('ALTER TABLE communication__news ADD CONSTRAINT FK_C17728FE953C1C61 FOREIGN KEY (source_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE communication__news ADD CONSTRAINT FK_C17728FEE92F8F78 FOREIGN KEY (recipient_id) REFERENCES color (id)');
		$this->addSql('CREATE INDEX IDX_C17728FE953C1C61 ON communication__news (source_id)');
		$this->addSql('CREATE INDEX IDX_C17728FEE92F8F78 ON communication__news (recipient_id)');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE communication__news DROP FOREIGN KEY FK_C17728FE953C1C61');
		$this->addSql('ALTER TABLE communication__news DROP FOREIGN KEY FK_C17728FEE92F8F78');
		$this->addSql('DROP INDEX IDX_C17728FE953C1C61 ON communication__news');
		$this->addSql('DROP INDEX IDX_C17728FEE92F8F78 ON communication__news');
		$this->addSql('ALTER TABLE communication__news ADD faction_id BINARY(16) DEFAULT NULL, DROP source_id, DROP recipient_id');
		$this->addSql('ALTER TABLE communication__news ADD CONSTRAINT `FK_C17728FE4448F8DA` FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('CREATE INDEX IDX_C17728FE4448F8DA ON communication__news (faction_id)');
	}
}
