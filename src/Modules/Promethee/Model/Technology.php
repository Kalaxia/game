<?php

/**
 * Technology.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 04.06.13
 */

namespace App\Modules\Promethee\Model;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Technology
{
	public function __construct(
		public Uuid $id,
		public Player $player,
		// @TODO convert to boolean (but beware the technologies with level which must remain integers)
		// technologies de débloquage (0 = bloqué, 1 = débloqué)
		public int $comPlatUnblock = 0,
		public int $dock2Unblock = 0,
		public int $dock3Unblock = 0,
		public int $recyclingUnblock = 0,
		public int $spatioportUnblock = 0,
		public int $ship0Unblock = 0,
		public int $ship1Unblock = 0,
		public int $ship2Unblock = 0,
		public int $ship3Unblock = 0,
		public int $ship4Unblock = 0,
		public int $ship5Unblock = 0,
		public int $ship6Unblock = 0,
		public int $ship7Unblock = 0,
		public int $ship8Unblock = 0,
		public int $ship9Unblock = 0,
		public int $ship10Unblock = 0,
		public int $ship11Unblock = 0,
		public int $colonization = 0,
		public int $conquest = 0,

		// technologies à niveau
		public int $generatorSpeed = 0,
		public int $refineryRefining = 0,
		public int $refineryStorage = 0,
		public int $dock1Speed = 0,
		public int $dock2Speed = 0,
		public int $technosphereSpeed = 0,
		public int $commercialIncomeUp = 0,
		public int $gravitModuleUp = 0,
		public int $dock3Speed = 0,
		public int $populationTaxUp = 0,
		public int $commanderInvestUp = 0,
		public int $uniInvestUp = 0,
		public int $antiSpyInvestUp = 0,
		public int $spaceShipsSpeed = 0,
		public int $spaceShipsContainer = 0, // soute

		public int $baseQuantity = 0,
		public int $fighterSpeed = 0,
		public int $fighterAttack = 0,
		public int $fighterDefense = 0,
		public int $corvetteSpeed = 0,
		public int $corvetteAttack = 0,
		public int $corvetteDefense = 0,
		public int $frigateSpeed = 0,
		public int $frigateAttack = 0,
		public int $frigateDefense = 0,
		public int $destroyerSpeed = 0,
		public int $destroyerAttack = 0,
		public int $destroyerDefense = 0,
	) {
	}

	public const COEF_POINTS = 1;
	public const COEF_TIME = 1;

	public function getTechnology(int $id): int
	{
		return match ($id) {
			TechnologyId::COM_PLAT_UNBLOCK => $this->comPlatUnblock,
			TechnologyId::DOCK2_UNBLOCK => $this->dock2Unblock,
			TechnologyId::DOCK3_UNBLOCK => $this->dock3Unblock,
			TechnologyId::RECYCLING_UNBLOCK => $this->recyclingUnblock,
			TechnologyId::SPATIOPORT_UNBLOCK => $this->spatioportUnblock,
			TechnologyId::SHIP0_UNBLOCK => $this->ship0Unblock,
			TechnologyId::SHIP1_UNBLOCK => $this->ship1Unblock,
			TechnologyId::SHIP2_UNBLOCK => $this->ship2Unblock,
			TechnologyId::SHIP3_UNBLOCK => $this->ship3Unblock,
			TechnologyId::SHIP4_UNBLOCK => $this->ship4Unblock,
			TechnologyId::SHIP5_UNBLOCK => $this->ship5Unblock,
			TechnologyId::SHIP6_UNBLOCK => $this->ship6Unblock,
			TechnologyId::SHIP7_UNBLOCK => $this->ship7Unblock,
			TechnologyId::SHIP8_UNBLOCK => $this->ship8Unblock,
			TechnologyId::SHIP9_UNBLOCK => $this->ship9Unblock,
			TechnologyId::SHIP10_UNBLOCK => $this->ship10Unblock,
			TechnologyId::SHIP11_UNBLOCK => $this->ship11Unblock,
			TechnologyId::COLONIZATION => $this->colonization,
			TechnologyId::CONQUEST => $this->conquest,
			TechnologyId::GENERATOR_SPEED => $this->generatorSpeed,
			TechnologyId::REFINERY_REFINING => $this->refineryRefining,
			TechnologyId::REFINERY_STORAGE => $this->refineryStorage,
			TechnologyId::DOCK1_SPEED => $this->dock1Speed,
			TechnologyId::DOCK2_SPEED => $this->dock2Speed,
			TechnologyId::TECHNOSPHERE_SPEED => $this->technosphereSpeed,
			TechnologyId::COMMERCIAL_INCOME => $this->commercialIncomeUp,
			TechnologyId::GRAVIT_MODULE => $this->gravitModuleUp,
			TechnologyId::DOCK3_SPEED => $this->dock3Speed,
			TechnologyId::POPULATION_TAX => $this->populationTaxUp,
			TechnologyId::COMMANDER_INVEST => $this->commanderInvestUp,
			TechnologyId::UNI_INVEST => $this->uniInvestUp,
			TechnologyId::ANTISPY_INVEST => $this->antiSpyInvestUp,
			TechnologyId::SPACESHIPS_SPEED => $this->spaceShipsSpeed,
			TechnologyId::SPACESHIPS_CONTAINER => $this->spaceShipsContainer,
			TechnologyId::BASE_QUANTITY => $this->baseQuantity,
			TechnologyId::FIGHTER_SPEED => $this->fighterSpeed,
			TechnologyId::FIGHTER_ATTACK => $this->fighterAttack,
			TechnologyId::FIGHTER_DEFENSE => $this->fighterDefense,
			TechnologyId::CORVETTE_SPEED => $this->corvetteSpeed,
			TechnologyId::CORVETTE_ATTACK => $this->corvetteAttack,
			TechnologyId::CORVETTE_DEFENSE => $this->corvetteDefense,
			TechnologyId::FRIGATE_SPEED => $this->frigateSpeed,
			TechnologyId::FRIGATE_ATTACK => $this->frigateAttack,
			TechnologyId::FRIGATE_DEFENSE => $this->frigateDefense,
			TechnologyId::DESTROYER_SPEED => $this->destroyerSpeed,
			TechnologyId::DESTROYER_ATTACK => $this->destroyerAttack,
			TechnologyId::DESTROYER_DEFENSE => $this->destroyerDefense,
			default => throw new \LogicException(sprintf('Technology with ID %d does not exist', $id)),
		};
	}

	public function setTechnology(int $id, int $value): void
	{
		switch ($id) {
			case TechnologyId::COM_PLAT_UNBLOCK:
				$this->comPlatUnblock = $value;
				break;
			case TechnologyId::DOCK2_UNBLOCK:
				$this->dock2Unblock = $value;
				break;
			case TechnologyId::DOCK3_UNBLOCK:
				$this->dock3Unblock = $value;
				break;
			case TechnologyId::RECYCLING_UNBLOCK:
				$this->recyclingUnblock = $value;
				break;
			case TechnologyId::SPATIOPORT_UNBLOCK:
				$this->spatioportUnblock = $value;
				break;
			case TechnologyId::SHIP0_UNBLOCK:
				$this->ship0Unblock = $value;
				break;
			case TechnologyId::SHIP1_UNBLOCK:
				$this->ship1Unblock = $value;
				break;
			case TechnologyId::SHIP2_UNBLOCK:
				$this->ship2Unblock = $value;
				break;
			case TechnologyId::SHIP3_UNBLOCK:
				$this->ship3Unblock = $value;
				break;
			case TechnologyId::SHIP4_UNBLOCK:
				$this->ship4Unblock = $value;
				break;
			case TechnologyId::SHIP5_UNBLOCK:
				$this->ship5Unblock = $value;
				break;
			case TechnologyId::SHIP6_UNBLOCK:
				$this->ship6Unblock = $value;
				break;
			case TechnologyId::SHIP7_UNBLOCK:
				$this->ship7Unblock = $value;
				break;
			case TechnologyId::SHIP8_UNBLOCK:
				$this->ship8Unblock = $value;
				break;
			case TechnologyId::SHIP9_UNBLOCK:
				$this->ship9Unblock = $value;
				break;
			case TechnologyId::SHIP10_UNBLOCK:
				$this->ship10Unblock = $value;
				break;
			case TechnologyId::SHIP11_UNBLOCK:
				$this->ship11Unblock = $value;
				break;
			case TechnologyId::COLONIZATION:
				$this->colonization = $value;
				break;
			case TechnologyId::CONQUEST:
				$this->conquest = $value;
				break;
			case TechnologyId::GENERATOR_SPEED:
				$this->generatorSpeed = $value;
				break;
			case TechnologyId::REFINERY_REFINING:
				$this->refineryRefining = $value;
				break;
			case TechnologyId::REFINERY_STORAGE:
				$this->refineryStorage = $value;
				break;
			case TechnologyId::DOCK1_SPEED:
				$this->dock1Speed = $value;
				break;
			case TechnologyId::DOCK2_SPEED:
				$this->dock2Speed = $value;
				break;
			case TechnologyId::TECHNOSPHERE_SPEED:
				$this->technosphereSpeed = $value;
				break;
			case TechnologyId::COMMERCIAL_INCOME:
				$this->commercialIncomeUp = $value;
				break;
			case TechnologyId::GRAVIT_MODULE:
				$this->gravitModuleUp = $value;
				break;
			case TechnologyId::DOCK3_SPEED:
				$this->dock3Speed = $value;
				break;
			case TechnologyId::POPULATION_TAX:
				$this->populationTaxUp = $value;
				break;
			case TechnologyId::COMMANDER_INVEST:
				$this->commanderInvestUp = $value;
				break;
			case TechnologyId::UNI_INVEST:
				$this->uniInvestUp = $value;
				break;
			case TechnologyId::ANTISPY_INVEST:
				$this->antiSpyInvestUp = $value;
				break;
			case TechnologyId::SPACESHIPS_SPEED:
				$this->spaceShipsSpeed = $value;
				break;
			case TechnologyId::SPACESHIPS_CONTAINER:
				$this->spaceShipsContainer = $value;
				break;
			case TechnologyId::BASE_QUANTITY:
				$this->baseQuantity = $value;
				break;
			case TechnologyId::FIGHTER_SPEED:
				$this->fighterSpeed = $value;
				break;
			case TechnologyId::FIGHTER_ATTACK:
				$this->fighterAttack = $value;
				break;
			case TechnologyId::FIGHTER_DEFENSE:
				$this->fighterDefense = $value;
				break;
			case TechnologyId::CORVETTE_SPEED:
				$this->corvetteSpeed = $value;
				break;
			case TechnologyId::CORVETTE_ATTACK:
				$this->corvetteAttack = $value;
				break;
			case TechnologyId::CORVETTE_DEFENSE:
				$this->corvetteDefense = $value;
				break;
			case TechnologyId::FRIGATE_SPEED:
				$this->frigateSpeed = $value;
				break;
			case TechnologyId::FRIGATE_ATTACK:
				$this->frigateAttack = $value;
				break;
			case TechnologyId::FRIGATE_DEFENSE:
				$this->frigateDefense = $value;
				break;
			case TechnologyId::DESTROYER_SPEED:
				$this->destroyerSpeed = $value;
				break;
			case TechnologyId::DESTROYER_ATTACK:
				$this->destroyerAttack = $value;
				break;
			case TechnologyId::DESTROYER_DEFENSE:
				$this->destroyerDefense = $value;
				break;
			default:
				throw new \LogicException(sprintf('Technology with ID %d does not exist', $id));
		}
	}
}
