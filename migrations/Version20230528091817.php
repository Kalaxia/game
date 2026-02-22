<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230528091817 extends AbstractMigration
{
	public function getDescription(): string
	{
		return '';
	}

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('SET FOREIGN_KEY_CHECKS = 0');
		$this->addSql('ALTER TABLE recyclingLog CHANGE mission_id mission_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
		$this->addSql('ALTER TABLE recyclingMission CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
		$this->addSql('SET FOREIGN_KEY_CHECKS = 1');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('SET FOREIGN_KEY_CHECKS = 0');
		$this->addSql('ALTER TABLE recyclingLog CHANGE mission_id mission_id INT UNSIGNED NOT NULL');
		$this->addSql('ALTER TABLE recyclingMission CHANGE id id INT UNSIGNED NOT NULL');
		$this->addSql('SET FOREIGN_KEY_CHECKS = 1');
	}
}
