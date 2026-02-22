<?php

namespace App\Modules\Athena\Handler\Building;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Message\Building\BuildingQueueMessage;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Domain\Service\UpdatePlanetPoints;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Zeus\Manager\PlayerManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class BuildingQueueHandler
{
	public function __construct(
		private PlayerManager $playerManager,
		private UpdatePlanetPoints $updatePlanetPoints,
		private PlanetRepositoryInterface $planetRepository,
		private BuildingQueueRepositoryInterface $buildingQueueRepository,
		private PlanetHelper $planetHelper,
		private BuildingLevelHandler $buildingLevelHandler,
		private LoggerInterface $logger,
	) {
	}

	public function __invoke(BuildingQueueMessage $message): void
	{
		$this->logger->info('Handle building completion for queue {queueId}', [
			'queueId' => $message->getBuildingQueueId(),
		]);
		if (null === ($queue = $this->buildingQueueRepository->get($message->getBuildingQueueId()))) {
			return;
		}
		$planet = $queue->base;
		$player = $planet->player;
		$this->buildingLevelHandler->increaseBuildingLevel($planet, $queue->buildingNumber);
		$this->updatePlanetPoints->updatePoints($planet);
		$this->planetRepository->save($planet);
		// increase player experience
		$experience = $this->planetHelper->getBuildingInfo($queue->buildingNumber, 'level', $queue->targetLevel, 'points');
		$this->playerManager->increaseExperience($player, $experience);

		// alert @TODO replace with Mercure
		//		if (($session = $this->clientManager->getSessionByPlayerId($player->getId())) !== null) {
		//			$session->addFlashbag('Construction de votre <strong>' . $this->orbitalBaseHelper->getBuildingInfo($queue->buildingNumber, 'frenchName') . ' niveau ' . $queue->targetLevel . '</strong> sur <strong>' . $planet->name . '</strong> terminée. Vous gagnez ' . $experience . ' point' . Format::addPlural($experience) . ' d\'expérience.', Flashbag::TYPE_GENERATOR_SUCCESS);
		//			$this->sessionWrapper->save($session);
		//		}
		$this->buildingQueueRepository->remove($queue);
		$this->logger->info('Construction done');
	}
}
