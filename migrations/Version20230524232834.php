<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524232834 extends AbstractMigration
{
	public function getDescription(): string
	{
		return '';
	}

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE buildingQueue (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', building_number INT NOT NULL, target_level INT NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ended_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_453D57286967DF41 (base_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE candidate (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', election_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, chief_choice SMALLINT NOT NULL, treasurer_choice SMALLINT NOT NULL, warlord_choice SMALLINT NOT NULL, minister_choice SMALLINT NOT NULL, program LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C8B28E44A708DAFF (election_id), INDEX IDX_C8B28E4499E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE color (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', identifier SMALLINT NOT NULL, alive TINYINT(1) DEFAULT 0 NOT NULL, is_winner TINYINT(1) DEFAULT 0 NOT NULL, credits INT UNSIGNED DEFAULT 0 NOT NULL, ranking_points INT UNSIGNED DEFAULT 0 NOT NULL, points INT UNSIGNED DEFAULT 0 NOT NULL, election_statement SMALLINT DEFAULT 0 NOT NULL, regime SMALLINT NOT NULL, is_closed TINYINT(1) DEFAULT 1 NOT NULL, is_in_game TINYINT(1) DEFAULT 0 NOT NULL, description TEXT DEFAULT NULL, victory_claimed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_election_held_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', relations JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE commander (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, base_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', start_place_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', destination_place_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(45) NOT NULL, comment LONGTEXT DEFAULT NULL, sexe SMALLINT DEFAULT 1 NOT NULL, age INT UNSIGNED DEFAULT 20 NOT NULL, avatar VARCHAR(20) NOT NULL, level SMALLINT UNSIGNED DEFAULT 1 NOT NULL, experience INT UNSIGNED DEFAULT 1 NOT NULL, palmares INT UNSIGNED DEFAULT 0 NOT NULL, statement SMALLINT UNSIGNED DEFAULT 0 NOT NULL, line SMALLINT UNSIGNED DEFAULT NULL, departed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', arrived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', enlisted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', assigned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', died_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', resources INT UNSIGNED DEFAULT 0 NOT NULL, travelType SMALLINT UNSIGNED DEFAULT NULL, travelLength SMALLINT UNSIGNED DEFAULT NULL, INDEX IDX_42D318BA99E6F5DF (player_id), INDEX IDX_42D318BA6967DF41 (base_id), INDEX IDX_42D318BA128CE42B (start_place_id), INDEX IDX_42D318BA53166228 (destination_place_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE commercialRoute (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', origin_base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', destination_base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', image_link VARCHAR(255) NOT NULL, distance INT NOT NULL, price INT NOT NULL, income INT NOT NULL, statement INT NOT NULL, proposed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', accepted_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8F5ED95ADC10CA2C (origin_base_id), INDEX IDX_8F5ED95A75EE039 (destination_base_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE commercialShipping (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', transaction_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED NOT NULL, origin_base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', destination_base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', resource_transported INT NOT NULL, ship_quantity INT NOT NULL, statement INT NOT NULL, departure_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', arrival_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C2B3CFBE2FC0CB0F (transaction_id), INDEX IDX_C2B3CFBE99E6F5DF (player_id), INDEX IDX_C2B3CFBEDC10CA2C (origin_base_id), INDEX IDX_C2B3CFBE75EE039 (destination_base_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE commercialTax (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', faction_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', related_faction_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', import_tax SMALLINT NOT NULL, export_tax SMALLINT NOT NULL, INDEX IDX_A8D93EBA4448F8DA (faction_id), INDEX IDX_A8D93EBACE85F06A (related_faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE conversation (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_message_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', messages_count SMALLINT NOT NULL, type SMALLINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE conversationMessage (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', conversation_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, content LONGTEXT NOT NULL, type SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B2FF54A09AC0396 (conversation_id), INDEX IDX_B2FF54A099E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE conversationUser (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', conversation_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, last_viewed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', player_status SMALLINT NOT NULL, conversation_status SMALLINT NOT NULL, INDEX IDX_127B926C9AC0396 (conversation_id), INDEX IDX_127B926C99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE creditTransaction (id INT UNSIGNED AUTO_INCREMENT NOT NULL, player_sender INT UNSIGNED DEFAULT NULL, player_receiver INT UNSIGNED DEFAULT NULL, faction_receiver BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', faction_sender BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', amount INT UNSIGNED NOT NULL, createdAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', comment LONGTEXT NOT NULL, discr VARCHAR(25) NOT NULL, INDEX IDX_6498C1C0C000DC11 (player_sender), INDEX IDX_6498C1C03B6FCD76 (player_receiver), INDEX IDX_6498C1C0BBD75021 (faction_receiver), INDEX IDX_6498C1C037E0807E (faction_sender), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE election (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', d_election DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DCA038004448F8DA (faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE factionNews (uuid INT NOT NULL, faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, o_content VARCHAR(255) NOT NULL, p_content VARCHAR(255) NOT NULL, pinned TINYINT(1) NOT NULL, statement SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FD1C34354448F8DA (faction_id), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE factionRanking (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', points SMALLINT NOT NULL, points_position SMALLINT NOT NULL, points_variation SMALLINT NOT NULL, new_points SMALLINT NOT NULL, general SMALLINT NOT NULL, general_position SMALLINT NOT NULL, general_variation SMALLINT NOT NULL, wealth SMALLINT NOT NULL, wealth_position SMALLINT NOT NULL, wealth_variation SMALLINT NOT NULL, territorial SMALLINT NOT NULL, territorial_position SMALLINT NOT NULL, territorial_variation SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DED62D7E4448F8DA (faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE forumMessage (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, topic_id INT UNSIGNED DEFAULT NULL, o_content LONGTEXT NOT NULL, p_content LONGTEXT NOT NULL, statement SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_989CDDB199E6F5DF (player_id), INDEX IDX_989CDDB11F55203D (topic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE forumTopic (id INT UNSIGNED NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE law (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', type SMALLINT NOT NULL, statement SMALLINT NOT NULL, vote_ended_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ended_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', for_vote SMALLINT NOT NULL, against_vote SMALLINT NOT NULL, options JSON NOT NULL, INDEX IDX_C0B552F4448F8DA (faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE notification (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_read TINYINT(1) NOT NULL, archived TINYINT(1) NOT NULL, INDEX IDX_BF5476CA99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE orbitalBase (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', place_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED NOT NULL, name VARCHAR(45) NOT NULL COLLATE `utf8_bin`, typeOfBase SMALLINT UNSIGNED DEFAULT 0 NOT NULL, levelGenerator SMALLINT UNSIGNED DEFAULT 0, levelRefinery SMALLINT UNSIGNED DEFAULT 0, levelDock1 SMALLINT UNSIGNED DEFAULT 0, levelDock2 SMALLINT UNSIGNED DEFAULT 0, levelDock3 SMALLINT UNSIGNED DEFAULT 0, levelTechnosphere SMALLINT UNSIGNED DEFAULT 0, levelCommercialPlateforme SMALLINT UNSIGNED DEFAULT 0, levelStorage SMALLINT UNSIGNED DEFAULT 0, levelRecycling SMALLINT UNSIGNED DEFAULT 0, levelSpatioport SMALLINT UNSIGNED DEFAULT 0, points INT UNSIGNED DEFAULT 0, iSchool INT UNSIGNED DEFAULT 0, iAntiSpy INT UNSIGNED DEFAULT 0, antiSpyAverage INT UNSIGNED DEFAULT 0, ship_storage JSON NOT NULL, resourcesStorage INT UNSIGNED DEFAULT 0, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_21A9A8CDDA6A219 (place_id), INDEX IDX_21A9A8CD99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE place (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, system_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', typeOfPlace SMALLINT UNSIGNED NOT NULL, position SMALLINT UNSIGNED NOT NULL, population SMALLINT UNSIGNED NOT NULL, coefResources DOUBLE PRECISION UNSIGNED NOT NULL, coefHistory SMALLINT UNSIGNED NOT NULL, resources INT UNSIGNED DEFAULT 0 NOT NULL, danger SMALLINT UNSIGNED DEFAULT 0 NOT NULL, maxDanger SMALLINT UNSIGNED DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_741D53CD99E6F5DF (player_id), INDEX IDX_741D53CDD0952FA5 (system_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE player (id INT UNSIGNED AUTO_INCREMENT NOT NULL, faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', god_father_id INT UNSIGNED DEFAULT NULL, bind VARCHAR(50) DEFAULT NULL, name VARCHAR(25) NOT NULL, avatar VARCHAR(12) NOT NULL, sex SMALLINT DEFAULT 1 NOT NULL, status SMALLINT UNSIGNED DEFAULT 1 NOT NULL, credit BIGINT UNSIGNED DEFAULT 0 NOT NULL, experience INT UNSIGNED DEFAULT 0 NOT NULL, factionPoint INT UNSIGNED DEFAULT 0 NOT NULL, level SMALLINT UNSIGNED DEFAULT 0 NOT NULL, victory INT UNSIGNED DEFAULT 0 NOT NULL, defeat INT UNSIGNED DEFAULT 0 NOT NULL, premium TINYINT(1) DEFAULT 0 NOT NULL, statement SMALLINT UNSIGNED DEFAULT 0 NOT NULL, description TEXT DEFAULT NULL, iUniversity INT UNSIGNED DEFAULT 0 NOT NULL, stepTutorial SMALLINT UNSIGNED NOT NULL, stepDone TINYINT(1) DEFAULT 0 NOT NULL, partNaturalSciences SMALLINT UNSIGNED DEFAULT 0 NOT NULL, partLifeSciences SMALLINT UNSIGNED DEFAULT 0 NOT NULL, partSocialPoliticalSciences SMALLINT UNSIGNED DEFAULT 0 NOT NULL, partInformaticEngineering SMALLINT UNSIGNED DEFAULT 0 NOT NULL, dInscription DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', dLastConnection DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', dLastActivity DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', uPlayer DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_98197A655E237E06 (name), INDEX IDX_98197A654448F8DA (faction_id), INDEX IDX_98197A65487CFC8E (god_father_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE playerRanking (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, general SMALLINT NOT NULL, general_position SMALLINT NOT NULL, general_variation SMALLINT NOT NULL, experience SMALLINT NOT NULL, experience_position SMALLINT NOT NULL, experience_variation SMALLINT NOT NULL, butcher SMALLINT NOT NULL, butcher_destroyed_pev SMALLINT NOT NULL, butcher_lost_pev SMALLINT NOT NULL, butcher_position SMALLINT NOT NULL, butcher_variation SMALLINT NOT NULL, trader SMALLINT NOT NULL, trader_position SMALLINT NOT NULL, trader_variation SMALLINT NOT NULL, fight SMALLINT NOT NULL, victories SMALLINT NOT NULL, defeat SMALLINT NOT NULL, fight_position SMALLINT NOT NULL, fight_variation SMALLINT NOT NULL, armies SMALLINT NOT NULL, armies_position SMALLINT NOT NULL, armies_variation SMALLINT NOT NULL, resources SMALLINT NOT NULL, resources_position SMALLINT NOT NULL, resources_variation SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2936711199E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE player_financial_report (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', initial_wallet INT NOT NULL, population_taxes INT NOT NULL, commercial_routes_income INT NOT NULL, faction_taxes INT NOT NULL, anti_spy_investments INT NOT NULL, university_investments INT NOT NULL, school_investments INT NOT NULL, commanders_wages INT NOT NULL, ships_cost INT NOT NULL, INDEX IDX_8E58A9AC99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE recyclingLog (id INT UNSIGNED NOT NULL, mission_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', resources INT NOT NULL, credits INT NOT NULL, ship0 INT NOT NULL, ship1 INT NOT NULL, ship2 INT NOT NULL, ship3 INT NOT NULL, ship4 INT NOT NULL, ship5 INT NOT NULL, ship6 INT NOT NULL, ship7 INT NOT NULL, ship8 INT NOT NULL, ship9 INT NOT NULL, ship10 INT NOT NULL, ship11 INT NOT NULL, INDEX IDX_1BE0779BE6CAE90 (mission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE recyclingMission (id INT UNSIGNED NOT NULL, base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', target_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', cycle_time SMALLINT NOT NULL, recycler_quantity SMALLINT NOT NULL, add_to_next_mission SMALLINT NOT NULL, statement SMALLINT NOT NULL, ended_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_ACD0882E6967DF41 (base_id), INDEX IDX_ACD0882E158E0B66 (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE report (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', attacker_id INT UNSIGNED DEFAULT NULL, defender_id INT UNSIGNED DEFAULT NULL, winner_id INT UNSIGNED DEFAULT NULL, attacker_commander_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', defender_commander_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', place_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', type SMALLINT NOT NULL, attacker_level INT NOT NULL, defender_level INT NOT NULL, attacker_experience INT NOT NULL, defender_experience INT NOT NULL, attacker_palmares INT NOT NULL, defender_palmares INT NOT NULL, resources INT NOT NULL, attacker_commander_experience INT NOT NULL, defender_commander_experience INT NOT NULL, earned_experience INT NOT NULL, is_legal TINYINT(1) NOT NULL, has_been_punished TINYINT(1) NOT NULL, round SMALLINT NOT NULL, attacker_pev_at_beginning SMALLINT NOT NULL, defender_pev_at_beginning SMALLINT NOT NULL, attacker_pev_at_end SMALLINT NOT NULL, defender_pev_at_end SMALLINT NOT NULL, attacker_statement SMALLINT NOT NULL, defender_statement SMALLINT NOT NULL, fought_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', squadrons JSON NOT NULL, attacker_army_in_begin JSON NOT NULL, defender_army_in_begin JSON NOT NULL, attacker_army_at_end JSON NOT NULL, defender_army_at_end JSON NOT NULL, attacker_total_in_begin JSON NOT NULL, defender_total_in_begin JSON NOT NULL, attacker_total_at_end JSON NOT NULL, defender_total_at_end JSON NOT NULL, attacker_difference JSON NOT NULL, defender_difference JSON NOT NULL, armies_done TINYINT(1) NOT NULL, INDEX IDX_C42F778465F8CAE3 (attacker_id), INDEX IDX_C42F77844A3E3B6F (defender_id), INDEX IDX_C42F77845DFCD4B8 (winner_id), INDEX IDX_C42F7784FADCEBA0 (attacker_commander_id), INDEX IDX_C42F7784254D233 (defender_commander_id), INDEX IDX_C42F7784DA6A219 (place_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE research (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, natural_to_pay INT NOT NULL, life_to_pay INT NOT NULL, social_to_pay INT NOT NULL, informatic_to_pay INT NOT NULL, math_level INT NOT NULL, phys_level INT NOT NULL, chem_level INT NOT NULL, bio_level INT NOT NULL, medi_level INT NOT NULL, econo_level INT NOT NULL, psycho_level INT NOT NULL, network_level INT NOT NULL, algo_level INT NOT NULL, stat_level INT NOT NULL, natural_tech INT NOT NULL, life_tech INT NOT NULL, social_tech INT NOT NULL, informatic_tech INT NOT NULL, INDEX IDX_57EB50C299E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE sector (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', identifier SMALLINT NOT NULL, xPosition SMALLINT UNSIGNED DEFAULT NULL, yPosition SMALLINT UNSIGNED DEFAULT NULL, xBarycentric SMALLINT UNSIGNED DEFAULT 0, yBarycentric SMALLINT UNSIGNED DEFAULT 0, tax SMALLINT UNSIGNED DEFAULT 0 NOT NULL, population INT UNSIGNED NOT NULL, lifePlanet INT UNSIGNED DEFAULT NULL, points INT UNSIGNED DEFAULT 1 NOT NULL, name VARCHAR(255) DEFAULT NULL, prime SMALLINT DEFAULT 0 NOT NULL, INDEX IDX_4BA3D9E84448F8DA (faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE shipQueue (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', base_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ended_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', dock_type SMALLINT NOT NULL, ship_number SMALLINT NOT NULL, quantity SMALLINT NOT NULL, INDEX IDX_413BF33D6967DF41 (base_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE spyReport (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED NOT NULL, place_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', place_faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', target_player_id INT UNSIGNED DEFAULT NULL, price INT NOT NULL, place_type INT NOT NULL, base_type INT DEFAULT NULL, place_name VARCHAR(124) DEFAULT NULL, resources INT NOT NULL, points INT NOT NULL, success_rate SMALLINT NOT NULL, type SMALLINT NOT NULL, target_player_level SMALLINT DEFAULT NULL, anti_spy_invest INT DEFAULT NULL, commercial_route_income INT DEFAULT NULL, ship_storage JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', commanders JSON NOT NULL, INDEX IDX_88C63CC799E6F5DF (player_id), INDEX IDX_88C63CC7DA6A219 (place_id), INDEX IDX_88C63CC76A886ED2 (place_faction_id), INDEX IDX_88C63CC7AD5287F3 (target_player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE squadron (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', commander_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', position SMALLINT UNSIGNED NOT NULL, line_coord SMALLINT UNSIGNED NOT NULL, ship0 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship1 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship2 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship3 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship4 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship5 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship6 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship7 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship8 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship9 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship10 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ship11 SMALLINT UNSIGNED DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5901B7573349A583 (commander_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE system (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', sector_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', faction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', xPosition SMALLINT UNSIGNED DEFAULT NULL, yPosition SMALLINT UNSIGNED DEFAULT NULL, typeOfSystem SMALLINT UNSIGNED DEFAULT NULL, INDEX IDX_C94D118BDE95C867 (sector_id), INDEX IDX_C94D118B4448F8DA (faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE technology (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED NOT NULL, com_plat_unblock SMALLINT NOT NULL, dock2_unblock SMALLINT NOT NULL, dock3_unblock SMALLINT NOT NULL, recycling_unblock SMALLINT NOT NULL, spatioport_unblock SMALLINT NOT NULL, ship0_unblock SMALLINT NOT NULL, ship1_unblock SMALLINT NOT NULL, ship2_unblock SMALLINT NOT NULL, ship3_unblock SMALLINT NOT NULL, ship4_unblock SMALLINT NOT NULL, ship5_unblock SMALLINT NOT NULL, ship6_unblock SMALLINT NOT NULL, ship7_unblock SMALLINT NOT NULL, ship8_unblock SMALLINT NOT NULL, ship9_unblock SMALLINT NOT NULL, ship10_unblock SMALLINT NOT NULL, ship11_unblock SMALLINT NOT NULL, colonization SMALLINT NOT NULL, conquest SMALLINT NOT NULL, generator_speed SMALLINT NOT NULL, refinery_refining SMALLINT NOT NULL, refinery_storage SMALLINT NOT NULL, dock1_speed SMALLINT NOT NULL, dock2_speed SMALLINT NOT NULL, technosphere_speed SMALLINT NOT NULL, commercial_income_up SMALLINT NOT NULL, gravit_module_up SMALLINT NOT NULL, dock3_speed SMALLINT NOT NULL, population_tax_up SMALLINT NOT NULL, commander_invest_up SMALLINT NOT NULL, uni_invest_up SMALLINT NOT NULL, anti_spy_invest_up SMALLINT NOT NULL, space_ships_speed SMALLINT NOT NULL, space_ships_container SMALLINT NOT NULL, base_quantity SMALLINT NOT NULL, fighter_speed SMALLINT NOT NULL, fighter_attack SMALLINT NOT NULL, fighter_defense SMALLINT NOT NULL, corvette_speed SMALLINT NOT NULL, corvette_attack SMALLINT NOT NULL, corvette_defense SMALLINT NOT NULL, frigate_speed SMALLINT NOT NULL, frigate_attack SMALLINT NOT NULL, frigate_defense SMALLINT NOT NULL, destroyer_speed SMALLINT NOT NULL, destroyer_attack SMALLINT NOT NULL, destroyer_defense SMALLINT NOT NULL, INDEX IDX_F463524D99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE technologyQueue (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED NOT NULL, place_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', technology SMALLINT NOT NULL, target_level SMALLINT NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ended_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_451A5A7699E6F5DF (player_id), INDEX IDX_451A5A76DA6A219 (place_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE transaction (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED NOT NULL, base_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', type SMALLINT NOT NULL, identifier SMALLINT NOT NULL, quantity INT NOT NULL, price INT NOT NULL, commercial_ship_quantity SMALLINT NOT NULL, statement SMALLINT NOT NULL, current_rate DOUBLE PRECISION NOT NULL, published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', validated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_723705D199E6F5DF (player_id), INDEX IDX_723705D16967DF41 (base_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE vote (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', candidate_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', player_id INT UNSIGNED DEFAULT NULL, voted_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5A10856491BD8781 (candidate_id), INDEX IDX_5A10856499E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE voteLaw (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE buildingQueue ADD CONSTRAINT FK_453D57286967DF41 FOREIGN KEY (base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE candidate ADD CONSTRAINT FK_C8B28E44A708DAFF FOREIGN KEY (election_id) REFERENCES election (id)');
		$this->addSql('ALTER TABLE candidate ADD CONSTRAINT FK_C8B28E4499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE commander ADD CONSTRAINT FK_42D318BA99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE commander ADD CONSTRAINT FK_42D318BA6967DF41 FOREIGN KEY (base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE commander ADD CONSTRAINT FK_42D318BA128CE42B FOREIGN KEY (start_place_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE commander ADD CONSTRAINT FK_42D318BA53166228 FOREIGN KEY (destination_place_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE commercialRoute ADD CONSTRAINT FK_8F5ED95ADC10CA2C FOREIGN KEY (origin_base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE commercialRoute ADD CONSTRAINT FK_8F5ED95A75EE039 FOREIGN KEY (destination_base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE commercialShipping ADD CONSTRAINT FK_C2B3CFBE2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
		$this->addSql('ALTER TABLE commercialShipping ADD CONSTRAINT FK_C2B3CFBE99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE commercialShipping ADD CONSTRAINT FK_C2B3CFBEDC10CA2C FOREIGN KEY (origin_base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE commercialShipping ADD CONSTRAINT FK_C2B3CFBE75EE039 FOREIGN KEY (destination_base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE commercialTax ADD CONSTRAINT FK_A8D93EBA4448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE commercialTax ADD CONSTRAINT FK_A8D93EBACE85F06A FOREIGN KEY (related_faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE conversationMessage ADD CONSTRAINT FK_B2FF54A09AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
		$this->addSql('ALTER TABLE conversationMessage ADD CONSTRAINT FK_B2FF54A099E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE conversationUser ADD CONSTRAINT FK_127B926C9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
		$this->addSql('ALTER TABLE conversationUser ADD CONSTRAINT FK_127B926C99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE creditTransaction ADD CONSTRAINT FK_6498C1C0C000DC11 FOREIGN KEY (player_sender) REFERENCES player (id)');
		$this->addSql('ALTER TABLE creditTransaction ADD CONSTRAINT FK_6498C1C03B6FCD76 FOREIGN KEY (player_receiver) REFERENCES player (id)');
		$this->addSql('ALTER TABLE creditTransaction ADD CONSTRAINT FK_6498C1C0BBD75021 FOREIGN KEY (faction_receiver) REFERENCES color (id)');
		$this->addSql('ALTER TABLE creditTransaction ADD CONSTRAINT FK_6498C1C037E0807E FOREIGN KEY (faction_sender) REFERENCES color (id)');
		$this->addSql('ALTER TABLE election ADD CONSTRAINT FK_DCA038004448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE factionNews ADD CONSTRAINT FK_FD1C34354448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE factionRanking ADD CONSTRAINT FK_DED62D7E4448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE forumMessage ADD CONSTRAINT FK_989CDDB199E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE forumMessage ADD CONSTRAINT FK_989CDDB11F55203D FOREIGN KEY (topic_id) REFERENCES forumTopic (id)');
		$this->addSql('ALTER TABLE law ADD CONSTRAINT FK_C0B552F4448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE orbitalBase ADD CONSTRAINT FK_21A9A8CDDA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE orbitalBase ADD CONSTRAINT FK_21A9A8CD99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CDD0952FA5 FOREIGN KEY (system_id) REFERENCES system (id)');
		$this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A654448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65487CFC8E FOREIGN KEY (god_father_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE playerRanking ADD CONSTRAINT FK_2936711199E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE player_financial_report ADD CONSTRAINT FK_8E58A9AC99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE recyclingLog ADD CONSTRAINT FK_1BE0779BE6CAE90 FOREIGN KEY (mission_id) REFERENCES recyclingMission (id)');
		$this->addSql('ALTER TABLE recyclingMission ADD CONSTRAINT FK_ACD0882E6967DF41 FOREIGN KEY (base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE recyclingMission ADD CONSTRAINT FK_ACD0882E158E0B66 FOREIGN KEY (target_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778465F8CAE3 FOREIGN KEY (attacker_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F77844A3E3B6F FOREIGN KEY (defender_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F77845DFCD4B8 FOREIGN KEY (winner_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784FADCEBA0 FOREIGN KEY (attacker_commander_id) REFERENCES commander (id)');
		$this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784254D233 FOREIGN KEY (defender_commander_id) REFERENCES commander (id)');
		$this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784DA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE research ADD CONSTRAINT FK_57EB50C299E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE sector ADD CONSTRAINT FK_4BA3D9E84448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE shipQueue ADD CONSTRAINT FK_413BF33D6967DF41 FOREIGN KEY (base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE spyReport ADD CONSTRAINT FK_88C63CC799E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE spyReport ADD CONSTRAINT FK_88C63CC7DA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE spyReport ADD CONSTRAINT FK_88C63CC76A886ED2 FOREIGN KEY (place_faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE spyReport ADD CONSTRAINT FK_88C63CC7AD5287F3 FOREIGN KEY (target_player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE squadron ADD CONSTRAINT FK_5901B7573349A583 FOREIGN KEY (commander_id) REFERENCES commander (id)');
		$this->addSql('ALTER TABLE system ADD CONSTRAINT FK_C94D118BDE95C867 FOREIGN KEY (sector_id) REFERENCES sector (id)');
		$this->addSql('ALTER TABLE system ADD CONSTRAINT FK_C94D118B4448F8DA FOREIGN KEY (faction_id) REFERENCES color (id)');
		$this->addSql('ALTER TABLE technology ADD CONSTRAINT FK_F463524D99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE technologyQueue ADD CONSTRAINT FK_451A5A7699E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE technologyQueue ADD CONSTRAINT FK_451A5A76DA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
		$this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D199E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
		$this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D16967DF41 FOREIGN KEY (base_id) REFERENCES orbitalBase (id)');
		$this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A10856491BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id)');
		$this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A10856499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE buildingQueue DROP FOREIGN KEY FK_453D57286967DF41');
		$this->addSql('ALTER TABLE candidate DROP FOREIGN KEY FK_C8B28E44A708DAFF');
		$this->addSql('ALTER TABLE candidate DROP FOREIGN KEY FK_C8B28E4499E6F5DF');
		$this->addSql('ALTER TABLE commander DROP FOREIGN KEY FK_42D318BA99E6F5DF');
		$this->addSql('ALTER TABLE commander DROP FOREIGN KEY FK_42D318BA6967DF41');
		$this->addSql('ALTER TABLE commander DROP FOREIGN KEY FK_42D318BA128CE42B');
		$this->addSql('ALTER TABLE commander DROP FOREIGN KEY FK_42D318BA53166228');
		$this->addSql('ALTER TABLE commercialRoute DROP FOREIGN KEY FK_8F5ED95ADC10CA2C');
		$this->addSql('ALTER TABLE commercialRoute DROP FOREIGN KEY FK_8F5ED95A75EE039');
		$this->addSql('ALTER TABLE commercialShipping DROP FOREIGN KEY FK_C2B3CFBE2FC0CB0F');
		$this->addSql('ALTER TABLE commercialShipping DROP FOREIGN KEY FK_C2B3CFBE99E6F5DF');
		$this->addSql('ALTER TABLE commercialShipping DROP FOREIGN KEY FK_C2B3CFBEDC10CA2C');
		$this->addSql('ALTER TABLE commercialShipping DROP FOREIGN KEY FK_C2B3CFBE75EE039');
		$this->addSql('ALTER TABLE commercialTax DROP FOREIGN KEY FK_A8D93EBA4448F8DA');
		$this->addSql('ALTER TABLE commercialTax DROP FOREIGN KEY FK_A8D93EBACE85F06A');
		$this->addSql('ALTER TABLE conversationMessage DROP FOREIGN KEY FK_B2FF54A09AC0396');
		$this->addSql('ALTER TABLE conversationMessage DROP FOREIGN KEY FK_B2FF54A099E6F5DF');
		$this->addSql('ALTER TABLE conversationUser DROP FOREIGN KEY FK_127B926C9AC0396');
		$this->addSql('ALTER TABLE conversationUser DROP FOREIGN KEY FK_127B926C99E6F5DF');
		$this->addSql('ALTER TABLE creditTransaction DROP FOREIGN KEY FK_6498C1C0C000DC11');
		$this->addSql('ALTER TABLE creditTransaction DROP FOREIGN KEY FK_6498C1C03B6FCD76');
		$this->addSql('ALTER TABLE creditTransaction DROP FOREIGN KEY FK_6498C1C0BBD75021');
		$this->addSql('ALTER TABLE creditTransaction DROP FOREIGN KEY FK_6498C1C037E0807E');
		$this->addSql('ALTER TABLE election DROP FOREIGN KEY FK_DCA038004448F8DA');
		$this->addSql('ALTER TABLE factionNews DROP FOREIGN KEY FK_FD1C34354448F8DA');
		$this->addSql('ALTER TABLE factionRanking DROP FOREIGN KEY FK_DED62D7E4448F8DA');
		$this->addSql('ALTER TABLE forumMessage DROP FOREIGN KEY FK_989CDDB199E6F5DF');
		$this->addSql('ALTER TABLE forumMessage DROP FOREIGN KEY FK_989CDDB11F55203D');
		$this->addSql('ALTER TABLE law DROP FOREIGN KEY FK_C0B552F4448F8DA');
		$this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA99E6F5DF');
		$this->addSql('ALTER TABLE orbitalBase DROP FOREIGN KEY FK_21A9A8CDDA6A219');
		$this->addSql('ALTER TABLE orbitalBase DROP FOREIGN KEY FK_21A9A8CD99E6F5DF');
		$this->addSql('ALTER TABLE place DROP FOREIGN KEY FK_741D53CD99E6F5DF');
		$this->addSql('ALTER TABLE place DROP FOREIGN KEY FK_741D53CDD0952FA5');
		$this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A654448F8DA');
		$this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65487CFC8E');
		$this->addSql('ALTER TABLE playerRanking DROP FOREIGN KEY FK_2936711199E6F5DF');
		$this->addSql('ALTER TABLE player_financial_report DROP FOREIGN KEY FK_8E58A9AC99E6F5DF');
		$this->addSql('ALTER TABLE recyclingLog DROP FOREIGN KEY FK_1BE0779BE6CAE90');
		$this->addSql('ALTER TABLE recyclingMission DROP FOREIGN KEY FK_ACD0882E6967DF41');
		$this->addSql('ALTER TABLE recyclingMission DROP FOREIGN KEY FK_ACD0882E158E0B66');
		$this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F778465F8CAE3');
		$this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F77844A3E3B6F');
		$this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F77845DFCD4B8');
		$this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784FADCEBA0');
		$this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784254D233');
		$this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784DA6A219');
		$this->addSql('ALTER TABLE research DROP FOREIGN KEY FK_57EB50C299E6F5DF');
		$this->addSql('ALTER TABLE sector DROP FOREIGN KEY FK_4BA3D9E84448F8DA');
		$this->addSql('ALTER TABLE shipQueue DROP FOREIGN KEY FK_413BF33D6967DF41');
		$this->addSql('ALTER TABLE spyReport DROP FOREIGN KEY FK_88C63CC799E6F5DF');
		$this->addSql('ALTER TABLE spyReport DROP FOREIGN KEY FK_88C63CC7DA6A219');
		$this->addSql('ALTER TABLE spyReport DROP FOREIGN KEY FK_88C63CC76A886ED2');
		$this->addSql('ALTER TABLE spyReport DROP FOREIGN KEY FK_88C63CC7AD5287F3');
		$this->addSql('ALTER TABLE squadron DROP FOREIGN KEY FK_5901B7573349A583');
		$this->addSql('ALTER TABLE system DROP FOREIGN KEY FK_C94D118BDE95C867');
		$this->addSql('ALTER TABLE system DROP FOREIGN KEY FK_C94D118B4448F8DA');
		$this->addSql('ALTER TABLE technology DROP FOREIGN KEY FK_F463524D99E6F5DF');
		$this->addSql('ALTER TABLE technologyQueue DROP FOREIGN KEY FK_451A5A7699E6F5DF');
		$this->addSql('ALTER TABLE technologyQueue DROP FOREIGN KEY FK_451A5A76DA6A219');
		$this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D199E6F5DF');
		$this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D16967DF41');
		$this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A10856491BD8781');
		$this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A10856499E6F5DF');
		$this->addSql('DROP TABLE buildingQueue');
		$this->addSql('DROP TABLE candidate');
		$this->addSql('DROP TABLE color');
		$this->addSql('DROP TABLE commander');
		$this->addSql('DROP TABLE commercialRoute');
		$this->addSql('DROP TABLE commercialShipping');
		$this->addSql('DROP TABLE commercialTax');
		$this->addSql('DROP TABLE conversation');
		$this->addSql('DROP TABLE conversationMessage');
		$this->addSql('DROP TABLE conversationUser');
		$this->addSql('DROP TABLE creditTransaction');
		$this->addSql('DROP TABLE election');
		$this->addSql('DROP TABLE factionNews');
		$this->addSql('DROP TABLE factionRanking');
		$this->addSql('DROP TABLE forumMessage');
		$this->addSql('DROP TABLE forumTopic');
		$this->addSql('DROP TABLE law');
		$this->addSql('DROP TABLE notification');
		$this->addSql('DROP TABLE orbitalBase');
		$this->addSql('DROP TABLE place');
		$this->addSql('DROP TABLE player');
		$this->addSql('DROP TABLE playerRanking');
		$this->addSql('DROP TABLE player_financial_report');
		$this->addSql('DROP TABLE recyclingLog');
		$this->addSql('DROP TABLE recyclingMission');
		$this->addSql('DROP TABLE report');
		$this->addSql('DROP TABLE research');
		$this->addSql('DROP TABLE sector');
		$this->addSql('DROP TABLE shipQueue');
		$this->addSql('DROP TABLE spyReport');
		$this->addSql('DROP TABLE squadron');
		$this->addSql('DROP TABLE system');
		$this->addSql('DROP TABLE technology');
		$this->addSql('DROP TABLE technologyQueue');
		$this->addSql('DROP TABLE transaction');
		$this->addSql('DROP TABLE vote');
		$this->addSql('DROP TABLE voteLaw');
	}
}
