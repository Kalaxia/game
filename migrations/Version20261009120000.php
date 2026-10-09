<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fleet combat Phase 7: battles in the game.
 *
 * - Squadrons hold a single ship type on a cell of the formation grid (D9). Legacy mixed squadrons are not
 *   converted: their ships go back to the hangar of the fleet's base, and fleets on the move are brought
 *   back to their base with their cargo (D18).
 * - Officers get traits (D20) and a link to the battle they fight in; battles get their planet and participants.
 * - Legacy combat reports are deleted (D21).
 */
final class Version20261009120000 extends AbstractMigration
{
	private const array MOVING_STATEMENTS = [2];
	private const int AFFECTED = 1;

	public function getDescription(): string
	{
		return 'Single-type squadrons on a formation grid, battles in the game, officer traits; legacy fleets returned to their hangars and legacy reports deleted';
	}

	public function up(Schema $schema): void
	{
		$this->returnShipsToHangars();
		$this->bringMovingFleetsHome();

		$this->addSql('DELETE FROM squadron');
		$this->addSql('ALTER TABLE squadron DROP FOREIGN KEY `FK_5901B7573349A583`');
		$this->addSql('ALTER TABLE squadron ADD ship_category SMALLINT UNSIGNED NOT NULL, ADD ships SMALLINT UNSIGNED NOT NULL, ADD q SMALLINT NOT NULL, ADD r SMALLINT NOT NULL, DROP position, DROP line_coord, DROP ship0, DROP ship1, DROP ship2, DROP ship3, DROP ship4, DROP ship5, DROP ship6, DROP ship7, DROP ship8, DROP ship9, DROP ship10, DROP ship11, CHANGE commander_id commander_id BINARY(16) NOT NULL');
		$this->addSql('ALTER TABLE squadron ADD CONSTRAINT FK_5901B7573349A583 FOREIGN KEY (commander_id) REFERENCES commander (id) ON DELETE CASCADE');

		$this->addSql('ALTER TABLE commander ADD traits JSON DEFAULT \'[]\' NOT NULL, ADD battle_id BINARY(16) DEFAULT NULL, DROP line');
		$this->addSql('ALTER TABLE commander ADD CONSTRAINT FK_42D318BAC9732719 FOREIGN KEY (battle_id) REFERENCES battle (id) ON DELETE SET NULL');
		$this->addSql('CREATE INDEX IDX_42D318BAC9732719 ON commander (battle_id)');

		$this->addSql('ALTER TABLE battle ADD place_id BINARY(16) DEFAULT NULL');
		$this->addSql('ALTER TABLE battle ADD CONSTRAINT FK_13991734DA6A219 FOREIGN KEY (place_id) REFERENCES galaxy__planets (id) ON DELETE SET NULL');
		$this->addSql('CREATE INDEX IDX_13991734DA6A219 ON battle (place_id)');
		$this->addSql('CREATE TABLE battle_player (battle_id BINARY(16) NOT NULL, player_id INT UNSIGNED NOT NULL, INDEX IDX_BD6F9394C9732719 (battle_id), INDEX IDX_BD6F939499E6F5DF (player_id), PRIMARY KEY (battle_id, player_id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('ALTER TABLE battle_player ADD CONSTRAINT FK_BD6F9394C9732719 FOREIGN KEY (battle_id) REFERENCES battle (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE battle_player ADD CONSTRAINT FK_BD6F939499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');

		$this->addSql('DROP TABLE report');
	}

	public function down(Schema $schema): void
	{
		$this->throwIrreversibleMigrationException('Legacy squadrons and combat reports cannot be restored.');
	}

	/**
	 * Adds the ships of every squadron to the hangar of its officer's base.
	 */
	private function returnShipsToHangars(): void
	{
		$columns = implode(' + ', array_map(fn (int $i) => sprintf('s.ship%d', $i), range(0, 11)));
		$rows = $this->connection->fetchAllAssociative(sprintf(
			'SELECT c.base_id, %s FROM squadron s JOIN commander c ON c.id = s.commander_id WHERE c.base_id IS NOT NULL AND (%s) > 0',
			implode(', ', array_map(fn (int $i) => sprintf('s.ship%d', $i), range(0, 11))),
			$columns,
		));

		$shipsByBase = [];
		foreach ($rows as $row) {
			for ($i = 0; $i < 12; ++$i) {
				$shipsByBase[$row['base_id']][$i] = ($shipsByBase[$row['base_id']][$i] ?? 0) + (int) $row['ship'.$i];
			}
		}

		foreach ($shipsByBase as $baseId => $ships) {
			$storage = json_decode((string) $this->connection->fetchOne('SELECT ship_storage FROM galaxy__planets WHERE id = ?', [$baseId]), true) ?: [];

			foreach ($ships as $category => $quantity) {
				$storage[$category] = ($storage[$category] ?? 0) + $quantity;
			}
			ksort($storage);

			$this->connection->executeStatement('UPDATE galaxy__planets SET ship_storage = ? WHERE id = ?', [json_encode($storage), $baseId]);
		}
	}

	/**
	 * Fleets on the move are back in orbit of their base, with the resources they carried.
	 * The pending arrival messages are ignored, the officers being no longer on the move.
	 */
	private function bringMovingFleetsHome(): void
	{
		$this->connection->executeStatement(
			'UPDATE galaxy__planets p JOIN commander c ON c.base_id = p.id SET p.resources_storage = p.resources_storage + c.resources WHERE c.statement IN (?) AND c.resources > 0',
			[self::MOVING_STATEMENTS],
			[\Doctrine\DBAL\ArrayParameterType::INTEGER],
		);
		$this->connection->executeStatement(
			'UPDATE commander SET statement = ?, resources = 0, travelType = NULL, departed_at = NULL, arrived_at = NULL, start_place_id = NULL, destination_place_id = NULL WHERE statement IN (?) AND base_id IS NOT NULL',
			[self::AFFECTED, self::MOVING_STATEMENTS],
			[\Doctrine\DBAL\ParameterType::INTEGER, \Doctrine\DBAL\ArrayParameterType::INTEGER],
		);
	}
}
