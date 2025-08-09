<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Event\Fleet\SquadronUpdateEvent;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\SquadronRepositoryInterface;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class UpdateSquadron extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		CommanderManager $commanderManager,
		CommanderArmyHandler $commanderArmyHandler,
		CommanderRepositoryInterface $commanderRepository,
		SquadronRepositoryInterface $squadronRepository,
		EventDispatcherInterface $eventDispatcher,
		EntityManagerInterface $entityManager,
		Uuid $id,
		int $squadronId,
	): Response {
		$payload = $request->toArray();

		if (empty($payload['army']) || empty($payload['base_id'])) {
			throw new BadRequestHttpException('Pas assez d\'informations pour assigner un vaisseau.');
		}

		$newSquadron = array_map(fn ($el) => $el > 0 ? (int) $el : 0, $payload['army']);

		if (12 !== count($newSquadron)) {
			throw new BadRequestHttpException('Pas assez d\'informations pour assigner un vaisseau.');
		}

		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');
		if (!Uuid::isValid($payload['base_id'])) {
			throw new BadRequestHttpException('Invalid UUID given for base ID');
		}

		$base = $orbitalBaseRepository->get(Uuid::fromString($payload['base_id'])) ?? throw $this->createNotFoundException('Base not found');

		// TODO add check on belonging player for multifleet
		if (!$commander->base->id->equals($base->id)) {
			throw new ConflictHttpException('This commander is not located on this base');
		}

		if (!$commander->isAffected()) {
			throw new BadRequestHttpException('This commander is not in orbit.');
		}

		$commanderArmyHandler->setArmy($commander);
		$squadron = $commander->getSquadron($squadronId);

		$squadronSHIP = $squadron->getShips();
		$baseSHIP = $base->getShipStorage();

		foreach ($newSquadron as $shipNumber => $quantity) {
			$baseSHIP[$shipNumber] -= ($quantity - $squadronSHIP[$shipNumber]);
			$squadronSHIP[$shipNumber] = $quantity;
		}

		// token de vérification
		$baseOK = true;
		$squadronOK = true;
		$totalPEV = 0;
		// vérif shipStorage (pas de nombre négatif)
		foreach ($baseSHIP as $i => $v) {
			if ($v < 0) {
				$baseOK = false;
				break;
			}
		}

		// vérif de squadron (pas plus de 100 PEV, pas de nombre négatif)
		foreach ($squadronSHIP as $i => $v) {
			$totalPEV += $v * $getShipCategoriesConfiguration($i, 'pev');
			if ($v < 0) {
				$squadronOK = false;
				break;
			}
		}

		if (!$baseOK || !$squadronOK || $totalPEV > 100) {
			throw new BadRequestHttpException('Erreur dans la répartition des vaisseaux.');
		}

		$base->shipStorage = $baseSHIP;
		$squadron->setShips($squadronSHIP);

		$orbitalBaseRepository->save($base);
		$squadronRepository->save($squadron);

		$eventDispatcher->dispatch(new SquadronUpdateEvent($commander, $squadron, $currentPlayer));

		return new Response('', Response::HTTP_NO_CONTENT);
	}
}
