<?php

namespace App\Modules\Athena\Handler\Ship;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Message\Ship\ShipQueueMessage;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ShipQueueHandler
{
	public function __construct(
		private PlayerManager                $playerManager,
		private ShipQueueRepositoryInterface $shipQueueRepository,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function __invoke(ShipQueueMessage $message): void
	{
		if (null === ($queue = $this->shipQueueRepository->get($message->getShipQueueId()))) {
			return;
		}
		$orbitalBase = $queue->base;
		$player = $orbitalBase->player;
		// vaisseau construit
		$orbitalBase->addShips($queue->shipNumber, $queue->quantity);
		// increase player experience
		$experience = $queue->quantity * ($this->getShipCategoriesConfiguration)($queue->shipNumber, 'points');
		$this->playerManager->increaseExperience($player, $experience);

		// alert @TODO replace with Mercure
		//		if (($session = $this->clientManager->getSessionByPlayerId($player->getId())) !== null) {
		//			$shipName = ($this->getShipCategoriesConfiguration)($queue->shipNumber, 'name');
		//			$session->addFlashbag(\sprintf(
		//				'Construction de %s</strong> sur <strong>%s</strong> terminée. Vous gagnez %s point%s d\'expérience.',
		//				($queue->quantity > 1)
		//					? \sprintf('vos <strong>%s %ss', $queue->quantity, $shipName)
		//					: \sprintf('votre %s<strong>', $shipName),
		//				$orbitalBase->name,
		//				$experience,
		//				Format::addPlural($experience),
		//			), 1 === $queue->dockType ? Flashbag::TYPE_DOCK1_SUCCESS : Flashbag::TYPE_DOCK2_SUCCESS);
		//			$this->sessionWrapper->save($session);
		//		}
		$this->shipQueueRepository->remove($queue);
	}
}
