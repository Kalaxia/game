<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Manager;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Domain\Service\UpdatePlanetPoints;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Zeus\Domain\Event\UniversityInvestmentsUpdateEvent;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

// @TODO Remove bounds to sessions
// TODO reapply readonly when service has been simplified
readonly class PlayerManager
{
	public function __construct(
		private PlayerRepositoryInterface       $playerRepository,
		private EventDispatcherInterface        $eventDispatcher,
		private EntityManagerInterface          $entityManager,
		private UpdatePlanetPoints              $updatePlanetPoints,
		private PlanetRepositoryInterface       $planetRepository,
		private PlaceManager                    $placeManager,
		private PlaceRepositoryInterface        $placeRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private SectorRepositoryInterface       $sectorRepository,
		private TechnologyRepositoryInterface   $technologyRepository,
		private UrlGeneratorInterface           $urlGenerator,
		#[Autowire('%zeus.player.base_level%')]
		private int                             $playerBaseLevel,
		#[Autowire('%server_id%')]
		private int                             $serverId,
	) {
	}

	public function kill(Player $player): void
	{
		// API call
		// TODO Replace when portal is implemented
		// $this->api->playerIsDead($player->bind, $this->serverId);

		// check if there is no other player with the same dead-name
		$futureName = sprintf('&#8224; %s ', $player->name);
		while (true) {
			// on ajoute un 'I' à chaque fois
			$futureName .= 'I';
			if (($otherPlayer = $this->playerRepository->getByName($futureName)) === null) {
				break;
			}
			$this->entityManager->clear();
		}
		// deadify the player
		$player->name = $futureName;
		$player->statement = Player::DEAD;

		$this->entityManager->flush();
	}

	public function reborn(Player $player): void
	{
		// sector choice
		$sectors = $this->sectorRepository->getFactionSectors($player->faction);

		$placeFound = false;
		$placeId = null;
		foreach ($sectors as $sector) {
			$placeIds = $this->planetRepository->getCandidatePlanetsForNewPlayers($sector);
			if ([] !== $placeIds) {
				$placeFound = true;
				$placeId = $placeIds[random_int(0, count($placeIds) - 1)];
				break;
			}
		}

		if ($placeFound) {
			// reinitialize some values of the player
			$player->iUniversity = 1000;
			$player->partNaturalSciences = 25;
			$player->partLifeSciences = 25;
			$player->partSocialPoliticalSciences = 25;
			$player->partInformaticEngineering = 25;
			$player->statement = Player::ACTIVE;
			$player->factionPoint = 0;

			$technos = $this->technologyRepository->getPlayerTechnology($player);
			$levelAE = $technos->getTechnology(TechnologyId::BASE_QUANTITY);
			if (0 != $levelAE) {
				// @TODO Beware possible regression here
				$technos->setTechnology(TechnologyId::BASE_QUANTITY, 0);
			}

			$planet = $this->planetRepository->get($placeId)
				?? throw new \LogicException('Planet not found');

			// attribute new base and place to player
			$planet->player = $player;
			$planet->name = 'Colonie';
			$planet->iSchool = 500;
			$planet->iAntiSpy = 500;
			$planet->resourcesStorage = 1000;

			$this->updatePlanetPoints->updatePoints($planet);

			$this->planetRepository->save($planet);

			$this->placeManager->turnAsSpawnPlace($planet);

			// envoi d'une notif
			$notif = NotificationBuilder::new()
				->setTitle('Nouvelle Colonie')
				->setContent(NotificationBuilder::paragraph(
					'Vous vous êtes malheureusement fait prendre votre dernière planète.
					Une nouvelle colonie vous a été attribuée'
				))
				->for($player);
			$this->notificationRepository->save($notif);
			$this->entityManager->flush();
		} else {
			// si on ne trouve pas de lieu pour le faire poper ou si la faction n'a plus de secteur, le joueur meurt
			$this->kill($player);
		}
	}

	// OBJECT METHOD
	public function increaseCredit(Player $player, int $credit): void
	{
		$player->credit += abs($credit);

		$this->playerRepository->updatePlayerCredits($player, abs($credit));
	}

	public function decreaseCredit(Player $player, int $credit): void
	{
		$credits =
			(abs($credit) > $player->credit)
			? 0
			: abs($credit)
		;
		$player->credit -= $credits;
		$this->playerRepository->updatePlayerCredits($player, -$credits);
	}

	public function increaseExperience(Player $player, $exp): void
	{
		$exp = intval(round($exp));
		$player->experience += $exp;
		$nextLevel = $this->playerBaseLevel * 2 ** ($player->level - 1);
		if ($player->experience < $nextLevel) {
			$this->entityManager->flush();

			return;
		}
		++$player->level;

		$notification = NotificationBuilder::new()
			->setTitle('Niveau supérieur')
			->setContent(
				NotificationBuilder::paragraph(
					'Félicitations, vous gagnez un niveau, vous êtes ',
					NotificationBuilder::bold(sprintf('niveau %d', $player->level)),
					'.',
				),
				NotificationBuilder::divider(),
				match ($player->level) {
					2 => NotificationBuilder::paragraph(
						'Attention,
						à partir de maintenant vous ne bénéficiez plus de la protection des nouveaux arrivants,
						n\'importe quel joueur peut désormais piller votre planète.
						Pensez donc à développer vos flottes pour vous défendre.'
					),
					4 => NotificationBuilder::paragraph(
						'Attention, à partir de maintenant un joueur adverse peut conquérir votre planète !
						Si vous n\'en avez plus, le jeu est terminé pour vous.
						Pensez donc à étendre votre royaume en colonisant d\'autres planètes.'
					),
					default => '',
				}
			)
			->for($player);

		$this->notificationRepository->save($notification);

		// parrainage : au niveau 3, le parrain gagne 1M crédits
		if (3 == $player->level and null !== $player->godFather) {
			$godFather = $player->godFather;

			$this->increaseCredit($godFather, 1000000);

			$notification = NotificationBuilder::new()
				->setTitle('Récompense de parrainage')
				->setContent(
					NotificationBuilder::paragraph(
						'Un de vos filleuls a atteint le niveau 3. ',
						'Il s\'agit de ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $player->id]),
							$player->name,
						),
						'.',
					),
					NotificationBuilder::paragraph(
						'Vous venez de gagner 1\'000\'000 crédits.',
						'N\'hésitez pas à parrainer d\'autres personnes pour gagner encore plus.'
					)
				)
				->for($player->godFather);

			$this->notificationRepository->save($notification);
		}
		$this->entityManager->flush();
	}

	public function updateUniversityInvestment(Player $player, int $investment): void
	{
		$player->iUniversity = $investment;

		$this->entityManager->flush();

		$this->eventDispatcher->dispatch(new UniversityInvestmentsUpdateEvent($player, $investment));
	}
}
