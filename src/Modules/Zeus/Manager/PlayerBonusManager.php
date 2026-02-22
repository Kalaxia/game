<?php

namespace App\Modules\Zeus\Manager;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Domain\Exception\NoCurrentPlayerSetException;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class PlayerBonusManager
{
	public function __construct(
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private TechnologyRepositoryInterface $technologyRepository,
		private TechnologyHelper $technologyHelper,
		private LawRepositoryInterface $lawRepository,
	) {
	}

	public function getBonusByPlayer(Player $player): PlayerBonus
	{
		// Failsafe to avoid reloading the current player bonus
		try {
			if ($this->currentPlayerRegistry->get()->id === $player->id && $this->currentPlayerBonusRegistry->isInitialized()) {
				return $this->currentPlayerBonusRegistry->getPlayerBonus();
			}
		} catch (NoCurrentPlayerSetException) {
		}

		$technology = $this->technologyRepository->getPlayerTechnology($player);
		$playerBonus = new PlayerBonus($player, $technology);

		// remplissage de l'objet normalement
		// remplissage avec les technologies
		$this->fillFromTechnology($playerBonus);
		$this->addFactionBonus($playerBonus);
		$this->addLawBonus($playerBonus);

		// remplissage avec les cartes
		// ...

		return $playerBonus;
	}

	private function fillFromTechnology(PlayerBonus $playerBonus): void
	{
		foreach (TechnologyId::BONUS_TECHNOLOGIES_IDS as $technologyId) {
			$this->addTechnoToBonus($playerBonus, $technologyId, PlayerBonusId::fromTechnologyIdentifier($technologyId));
		}
	}

	private function addTechnoToBonus(PlayerBonus $playerBonus, $techno, $bonus): void
	{
		$totalBonus = 0;
		for ($i = 0; $i <= $playerBonus->technology->getTechnology($techno); ++$i) {
			$totalBonus += $this->technologyHelper->getImprovementPercentage($techno, $i);
		}
		$playerBonus->bonuses->add($bonus, $totalBonus);
	}

	private function addLawBonus(PlayerBonus $playerBonus): void
	{
		$laws = $this->lawRepository->getByFactionAndStatements($playerBonus->playerColor, [Law::EFFECTIVE]);
		foreach ($laws as $law) {
			switch ($law->type) {
				case Law::MILITARYSUBVENTION:
					$playerBonus->bonuses->increase(PlayerBonusId::DOCK1_SPEED, LawResources::getInfo($law->type, 'bonus'));
					$playerBonus->bonuses->increase(PlayerBonusId::DOCK2_SPEED, LawResources::getInfo($law->type, 'bonus'));
					$playerBonus->bonuses->increase(PlayerBonusId::REFINERY_REFINING, -10);
					$playerBonus->bonuses->increase(PlayerBonusId::REFINERY_REFINING, -10);
					break;
				case Law::TECHNOLOGYTRANSFER:
					$playerBonus->bonuses->increase(PlayerBonusId::TECHNOSPHERE_SPEED, LawResources::getInfo($law->type, 'bonus'));
					break;
				default:
					break;
			}
		}
	}

	private function addFactionBonus(PlayerBonus $playerBonus): void
	{
		$color = $playerBonus->playerColor ?? throw new \LogicException('Bonus must have a faction');

		$bonus = ($this->getFactionsConfiguration)($color, 'bonus');

		if (in_array(ColorResource::DEFENSELITTLESHIPBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::FIGHTER_DEFENSE, 5);
			$playerBonus->bonuses->increase(PlayerBonusId::CORVETTE_DEFENSE, 5);
			$playerBonus->bonuses->increase(PlayerBonusId::FRIGATE_DEFENSE, 5);
			$playerBonus->bonuses->increase(PlayerBonusId::DESTROYER_DEFENSE, 5);
		}

		if (in_array(ColorResource::SPEEDLITTLESHIPBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::FIGHTER_SPEED, 10);
			$playerBonus->bonuses->increase(PlayerBonusId::CORVETTE_SPEED, 10);
		}

		if (in_array(ColorResource::DEFENSELITTLESHIPMALUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::FRIGATE_DEFENSE, -5);
			$playerBonus->bonuses->increase(PlayerBonusId::DESTROYER_DEFENSE, -5);
		}

		if (in_array(ColorResource::COMMERCIALROUTEINCOMEBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::COMMERCIAL_INCOME, 5);
		}

		if (in_array(ColorResource::TAXBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::POPULATION_TAX, 3);
		}

		if (in_array(ColorResource::LOOTRESOURCESMALUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::SHIP_CONTAINER, -5);
		}

		if (in_array(ColorResource::RAFINERYBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::REFINERY_REFINING, 4);
		}

		if (in_array(ColorResource::STORAGEBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::REFINERY_STORAGE, 4);
		}

		if (in_array(ColorResource::BIGACADEMICBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::UNI_INVEST, 4);
		}

		if (in_array(ColorResource::COMMANDERSCHOOLBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::COMMANDER_INVEST, 6);
		}

		if (in_array(ColorResource::LITTLEACADEMICBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::UNI_INVEST, 2);
		}

		if (in_array(ColorResource::TECHNOLOGYBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::TECHNOSPHERE_SPEED, 2);
		}

		if (in_array(ColorResource::DEFENSELITTLESHIPBONUS, $bonus)) {
			$playerBonus->bonuses->increase(PlayerBonusId::FIGHTER_DEFENSE, 5);
			$playerBonus->bonuses->increase(PlayerBonusId::CORVETTE_DEFENSE, 5);
			$playerBonus->bonuses->increase(PlayerBonusId::FRIGATE_DEFENSE, 5);
			$playerBonus->bonuses->increase(PlayerBonusId::DESTROYER_DEFENSE, 5);
		}
	}

	public function increment(PlayerBonus $playerBonus, $bonusId, $increment): void
	{
		if (PlayerBonusId::isBonusId($bonusId)) {
			if ($increment > 0) {
				$playerBonus->bonuses->add($bonusId, $playerBonus->bonuses->get($bonusId) + $increment);
			} else {
				throw new \LogicException('incrémentation de bonus impossible - l\'incrément doit être positif');
			}
		} else {
			throw new \LogicException('incrémentation de bonus impossible - bonus invalide');
		}
	}

	public function decrement(PlayerBonus $playerBonus, $bonusId, $decrement): void
	{
		if (PlayerBonusId::isBonusId($bonusId)) {
			if ($decrement > 0) {
				if ($decrement <= $playerBonus->bonuses->get($bonusId)) {
					$playerBonus->bonuses->add($bonusId, $playerBonus->bonuses->get($bonusId) - $decrement);
				} else {
					throw new \LogicException('décrémentation de bonus impossible - le décrément est plus grand que le bonus');
				}
			} else {
				throw new \LogicException('décrémentation de bonus impossible - le décrément doit être positif');
			}
		} else {
			throw new \LogicException('décrémentation de bonus impossible - bonus invalide');
		}
	}

	public function updateTechnoBonus(PlayerBonus $playerBonus, $techno, $level): void
	{
		$this->addTechnoToBonus($playerBonus, $techno, PlayerBonusId::fromTechnologyIdentifier($techno));
	}
}
