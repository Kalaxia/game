<?php

namespace App\Modules\Zeus\Helper;

use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;

readonly class TutorialHelper
{
	public function __construct(
		private PlayerRepositoryInterface          $playerRepository,
		private PlanetRepositoryInterface          $planetRepository,
		private TechnologyRepositoryInterface      $technologyRepository,
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
	) {
	}

	/*public function checkTutorial(): void
	{
		// PAS UTILISEE POUR L'INSTANT (le sera quand il y aura une étape passive dans le tutoriel)
		$player = $this->sessionWrapper->get('playerId');
		$stepTutorial = $this->sessionWrapper->get('playerInfo')->get('stepTutorial');
		$stepDone = $this->sessionWrapper->get('playerInfo')->get('stepDone');

		if ($stepTutorial > 0) {
			if (false == $stepDone) {
				// check if current step is done

				// hint : checker seulement les actions passives
				switch ($stepTutorial) {
					case 1:
						$asdf = 'asdf';
						break;
					case 2:
						$jlk = 'jkl';
						break;
				}
			}
		}
	}*/

	public function setStepDone(Player $player): void
	{
		$player->stepDone = true;

		$this->playerRepository->save($player);
	}

	public function clearStepDone(Player $player): void
	{
		$player->stepDone = true;

		$this->playerRepository->save($player);
	}

	public function isNextBuildingStepAlreadyDone(Player $player, int $buildingId, int $level): bool
	{
		$nextStepAlreadyDone = false;

		$playerPlanets = $this->planetRepository->getPlayerPlanets($player);
		foreach ($playerPlanets as $planet) {
			if ($this->buildingLevelHandler->getBuildingLevel($planet, $buildingId) >= $level) {
				$nextStepAlreadyDone = true;
				break;
			} else {
				// verify in the queue
				$buildingQueues = $this->buildingQueueRepository->getPlanetQueues($planet);
				foreach ($buildingQueues as $buildingQueue) {
					if ($buildingQueue->buildingNumber == $buildingId and $buildingQueue->targetLevel >= $level) {
						$nextStepAlreadyDone = true;
						break;
					}
				}
			}
		}

		return $nextStepAlreadyDone;
	}

	public function isNextTechnoStepAlreadyDone(Player $player, int $technoId, int $level = 1): bool
	{
		$technology = $this->technologyRepository->getPlayerTechnology($player);
		if ($technology->getTechnology($technoId) >= $level) {
			return true;
		}
		// verify in the queue
		if (null !== $this->technologyQueueRepository->getPlayerTechnologyQueue($player, $technoId)) {
			return true;
		}

		return false;
	}
}
