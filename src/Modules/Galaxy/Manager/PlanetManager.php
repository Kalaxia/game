<?php

namespace App\Modules\Galaxy\Manager;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\GetMaxResourceStorage;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class PlanetManager
{
	public function __construct(
		private GetMaxResourceStorage                 $getMaxStorage,
		private CurrentPlayerPlanetsRegistry          $currentPlayerPlanetsRegistry,
		private TechnologyQueueRepositoryInterface    $technologyQueueRepository,
		private CommercialRouteManager                $commercialRouteManager,
		private CommercialShippingRepositoryInterface $commercialShippingRepository,
		private CommanderRepositoryInterface          $commanderRepository,
		private TransactionRepositoryInterface        $transactionRepository,
		private RecyclingMissionRepositoryInterface   $recyclingMissionRepository,
		private PlanetRepositoryInterface             $planetRepository,
		private EntityManagerInterface                $entityManager,
		private EventDispatcherInterface              $eventDispatcher,
		private CommanderManager                      $commanderManager,
	) {
	}

	/**
	 * @param list<Commander> $movingCommanders
	 */
	public function countPlayerPlanets(array $movingCommanders): int
	{
		$coloQuantity = \count(\array_filter(
			$movingCommanders,
			fn (Commander $commander) => CommanderMission::Colo === $commander->travelType,
		));

		return $coloQuantity + $this->currentPlayerPlanetsRegistry->count();
	}

	public function changeOwner(Planet $base, Player|null $newOwner): void
	{
		$baseCommanders = $this->commanderRepository->getPlanetCommanders($base);
		// changement de possesseur des offres du marché
		$transactions = $this->transactionRepository->getPlanetPropositions($base);

		foreach ($transactions as $transaction) {
			// change owner of transaction
			$transaction->rPlayer = $newOwner;

			$commercialShipping = $this->commercialShippingRepository->getByTransaction($transaction);
			// change owner of commercial shipping
			$commercialShipping->player = $newOwner;
		}

		// attribuer le rPlayer à la Base
		$oldOwner = $base->player;
		$base->player = $newOwner;

		// suppression des routes commerciales
		$this->commercialRouteManager->removeBaseRoutes($base);

		// suppression des technologies en cours de développement
		foreach ($this->technologyQueueRepository->getPlanetQueues($base) as $queue) {
			$this->technologyQueueRepository->remove($queue);
		}

		// suppression des missions de recyclages ainsi que des logs de recyclages
		$this->recyclingMissionRepository->removePlanetMissions($base);

		// mise des investissements à 0
		$base->iSchool = 0;
		$base->iAntiSpy = 0;

		// mise à jour de la date de création pour qu'elle soit dans l'ordre
		$base->createdAt = new \DateTimeImmutable();
		// rendre déserteuses les flottes en voyage
		foreach ($baseCommanders as $commander) {
			if (in_array($commander->statement, [Commander::INSCHOOL, Commander::ONSALE, Commander::RESERVE])) {
				$commander->player = $newOwner;
			} elseif ($commander->isMoving()) {
				// TODO replace "prise en vol"
				$this->commanderManager->endTravel($commander, Commander::RETIRED);
			// @TODO handle cancellation
			// $this->realtimeActionScheduler->cancel($commander, $commander->getArrivalDate());
			} else {
				$commander->statement = Commander::DEAD;
			}
		}

		$this->eventDispatcher->dispatch(new PlanetOwnerChangeEvent($base, $oldOwner));

		$this->entityManager->flush();
	}

	public function increaseResources(
        Planet $planet,
        int    $resources,
        bool   $persist = true
	): void {
		$planet->resourcesStorage = min(
			$planet->resourcesStorage + $resources,
			($this->getMaxStorage)(base: $planet, offLimits: true),
		);

		if (true === $persist) {
			$this->planetRepository->save($planet);
		}
	}

	public function decreaseResources(Planet $planet, int $resources): void
	{
		$planet->resourcesStorage = max($planet->resourcesStorage - $resources, 0);

		$this->planetRepository->save($planet);
	}
}
