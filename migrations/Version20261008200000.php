<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008200000 extends AbstractMigration
{
	public function getDescription(): string
	{
		return 'Store real-time battles and their turn log (fleet combat Phase 6)';
	}

	public function up(Schema $schema): void
	{
		$this->addSql('CREATE TABLE battle (state LONGBLOB NOT NULL, turn_duration SMALLINT NOT NULL, started_at DATETIME NOT NULL, turn SMALLINT NOT NULL, ended_at DATETIME DEFAULT NULL, winning_side SMALLINT DEFAULT NULL, id BINARY(16) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('CREATE TABLE battle_turn (turn SMALLINT NOT NULL, events JSON NOT NULL, resolved_at DATETIME NOT NULL, id BINARY(16) NOT NULL, battle_id BINARY(16) NOT NULL, INDEX IDX_7747D6D1C9732719 (battle_id), UNIQUE INDEX battle_turn_unique (battle_id, turn), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('ALTER TABLE battle_turn ADD CONSTRAINT FK_7747D6D1C9732719 FOREIGN KEY (battle_id) REFERENCES battle (id) ON DELETE CASCADE');
	}

	public function down(Schema $schema): void
	{
		$this->addSql('ALTER TABLE battle_turn DROP FOREIGN KEY FK_7747D6D1C9732719');
		$this->addSql('DROP TABLE battle_turn');
		$this->addSql('DROP TABLE battle');
	}
}
