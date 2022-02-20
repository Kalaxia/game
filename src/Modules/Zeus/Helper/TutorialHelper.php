<?php

/**
 * TutorialHelper
 *
 * @author Jacky Casas
 * @copyright Asylamba
 *
 * @package Zeus
 * @update 25.04.14
 */
namespace App\Modules\Zeus\Helper;

use App\Classes\Library\Session\SessionWrapper;

use App\Classes\Entity\EntityManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\BuildingQueueManager;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Model\Player;

class TutorialHelper
{
	public function __construct(
		protected EntityManager $entityManager,
		protected PlayerManager $playerManager,
		protected OrbitalBaseManager $orbitalBaseManager,
		protected BuildingQueueManager $buildingQueueManager,
		protected TechnologyQueueManager $technologyQueueManager,
		protected TechnologyManager $technologyManager,
		protected SessionWrapper $sessionWrapper
	) {
	}
	
	public function checkTutorial(): void
	{
		# PAS UTILISEE POUR L'INSTANT (le sera quand il y aura une étape passive dans le tutoriel)
		$player = $this->sessionWrapper->get('playerId');
		$stepTutorial = $this->sessionWrapper->get('playerInfo')->get('stepTutorial');
		$stepDone = $this->sessionWrapper->get('playerInfo')->get('stepDone');

		if ($stepTutorial > 0) {
			if ($stepDone == FALSE) {
				# check if current step is done

				# hint : checker seulement les actions passives
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
	}

	public function setStepDone(Player $player): void
	{
		$player->stepDone = true;
		
		$this->entityManager->flush($player);
	}

	public function clearStepDone(Player $player): void
	{
		$player->stepDone = true;

		$this->entityManager->flush($player);
	}

	public function isNextBuildingStepAlreadyDone($playerId, $buildingId, $level) {
		$nextStepAlreadyDone = FALSE;

		$playerBases = $this->orbitalBaseManager->getPlayerBases($playerId);
		foreach ($playerBases as $orbitalBase) { 
			if ($orbitalBase->getBuildingLevel($buildingId) >= $level) {
				$nextStepAlreadyDone = TRUE;
				break;
			} else {
				# verify in the queue
				$buildingQueues = $this->buildingQueueManager->getBaseQueues($orbitalBase->rPlace);
				foreach ($buildingQueues as $buildingQueue) {
					if ($buildingQueue->buildingNumber == $buildingId AND $buildingQueue->targetLevel >= $level) {
						$nextStepAlreadyDone = TRUE;
						break;
					} 
				}
			}
		}
		return $nextStepAlreadyDone;
	}

	public function isNextTechnoStepAlreadyDone($playerId, $technoId, $level = 1) {
		$technology = $this->technologyManager->getPlayerTechnology($playerId);
		if ($technology->getTechnology($technoId) >= $level) {
			return true;
		}
		// verify in the queue
		if (($this->technologyQueueManager->getPlayerTechnologyQueue($playerId, $technoId)) !== null) {
			return true;
		}
		return false;
	}
}
