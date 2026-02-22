<?php

namespace App\Modules\Galaxy\Manager;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Report;
use App\Modules\Galaxy\Domain\Entity\EmptyPlace;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

readonly class PlaceManager
{
	public function __construct(
		private NotificationRepositoryInterface $notificationRepository,
		private PlanetRepositoryInterface $planetRepository,
		private PlaceRepositoryInterface $placeRepository,
		private UrlGeneratorInterface $urlGenerator,
	) {
	}

	public function turnAsEmptyPlace(Place $place): void
	{
		$emptyPlace = new EmptyPlace(
			id: $place->id,
			system: $place->system,
			position: $place->position,
			updatedAt: new \DateTimeImmutable(),
		);

		$this->placeRepository->remove($place);
		$this->placeRepository->save($emptyPlace);
	}

	public function turnAsSpawnPlace(Planet $planet): void
	{
		$planet->coefResources = 60;
		$planet->coefHistory = 20;
		$planet->population = 50;

		$this->planetRepository->save($planet);
	}

	public function sendNotif(Planet $place, int $case, Commander $commander, ?Report $report = null): void
	{
		$notifications = match ($case) {
			Planet::CHANGESUCCESS => [
				NotificationBuilder::new()
					->setTitle('Déplacement réussi')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' est arrivé sur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'.',
					))
					->for($commander->player),
			],
			Planet::CHANGEFAIL => [
				NotificationBuilder::new()
					->setTitle('Déplacement réussi')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' s\'est posé sur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'. Il est en garnison car il n\'y avait pas assez de place en orbite.',
					))
					->for($commander->player),
			],
			Planet::CHANGELOST => [
				NotificationBuilder::new()
					->setTitle('Déplacement raté')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' n\'est pas arrivé sur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'. Cette base ne vous appartient pas. Elle a pu être conquise entre temps.',
					))
					->for($commander->player),
			],
			Planet::LOOTEMPTYSSUCCESS => [
				NotificationBuilder::new()
					->setTitle('Pillage réussi')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' a pillé la planète rebelle située aux coordonnées ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							Game::formatCoord(
								$place->system->xPosition,
								$place->system->xPosition,
								$place->position,
								$place->system->sector->identifier
							),
						),
						'.',
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_RESOURCE,
							Format::number($commander->resources),
							'ressources pillées',
						),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_XP,
							'+ '.Format::number($commander->earnedExperience),
							'expérience de l\'officier',
						),
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($commander->player),
			],
			Planet::LOOTEMPTYFAIL => [
				NotificationBuilder::new()
					->setTitle('Pillage raté')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('memorial', ['id' => $commander->id]),
							$commander->name,
						),
						' est tombé lors de l\'attaque de la planète rebelle située aux coordonnées ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							Game::formatCoord(
								$place->system->xPosition,
								$place->system->xPosition,
								$place->position,
								$place->system->sector->identifier
							),
						),
						'.',
						NotificationBuilder::divider(),
						'Il a désormais rejoint le Mémorial. Que son âme traverse l\'Univers dans la paix.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($commander->player),
			],
			Planet::LOOTPLAYERWHITBATTLESUCCESS => [
				NotificationBuilder::new()
					->setTitle('Pillage réussi')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' a pillé la planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						'.',
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_RESOURCE,
							Format::number($commander->resources),
							'ressources pillées',
						),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_XP,
							'+ '.Format::number($commander->earnedExperience),
							'expérience de l\'officier',
						),
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($commander->player),
				NotificationBuilder::new()
					->setTitle('Rapport de pillage')
					->setContent(NotificationBuilder::paragraph(
						'L\'officier ',
						NotificationBuilder::bold($commander->name),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $commander->player]),
							$commander->player->name,
						),
						' a pillé votre planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'.',
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_RESOURCE,
							Format::number($commander->resources),
							'ressources pillées',
						),
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($place->player),
			],
			Planet::LOOTPLAYERWHITBATTLEFAIL => [
				NotificationBuilder::new()
					->setTitle('Pillage raté')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('memorial', ['id' => $commander->id]),
							$commander->name,
						),
						' est tombé lors du pillage de la planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						'.',
						NotificationBuilder::divider(),
						'Il a désormais rejoint le Mémorial. Que son âme traverse l\'Univers dans la paix.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($commander->player),
				NotificationBuilder::new()
					->setTitle('Rapport de combat')
					->setContent(NotificationBuilder::paragraph(
						'L\'officier ',
						NotificationBuilder::bold($commander->name),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						' a attaqué votre planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'.',
						NotificationBuilder::divider(),
						'Vous avez repoussé l\'ennemi avec succès.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($place->player),
			],
			Planet::LOOTPLAYERWHITOUTBATTLESUCCESS => [
				NotificationBuilder::new()
					->setTitle('Pillage réussi')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' a pillé la planète non défendue ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						'.',
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_RESOURCE,
							Format::number($commander->resources),
							'ressources pillées',
						),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_XP,
							'+ '.Format::number($commander->earnedExperience),
							'expérience de l\'officier',
						),
					))
					->for($commander->player),
				NotificationBuilder::new()
					->setTitle('Rapport de pillage')
					->setContent(
						'L\'officier ',
						NotificationBuilder::bold($commander->name),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $commander->player]),
							$commander->player->name,
						),
						' a pillé votre planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'. Aucune flotte n\'était en position pour la défendre. ',
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_RESOURCE,
							Format::number($commander->resources),
							'ressources pillées',
						),
					)
					->for($place->player),
			],
			Planet::LOOTLOST => [
				NotificationBuilder::new()
					->setTitle('Erreur de coordonnées')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' n\'a pas attaqué la planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' car son joueur est de votre faction, sous la protection débutant ou un allié.',
					))
					->for($commander->player),
			],
			Planet::CONQUEREMPTYSSUCCESS => [
				NotificationBuilder::new()
					->setTitle('Colonisation réussie')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' a colonisé la planète rebelle située aux coordonnées ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							Game::formatCoord(
								$place->system->xPosition,
								$place->system->xPosition,
								$place->position,
								$place->system->sector->identifier
							),
						),
						NotificationBuilder::resourceBox(
							'xp',
							'+ '.Format::number($commander->earnedExperience),
							'expérience de l\'officier',
						),
						'Votre empire s\'étend, administrez votre ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('switchplanet', ['planetId' => $place->id]),
							'nouvelle planète',
						),
						'.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($commander->player),
			],
			Planet::CONQUEREMPTYFAIL => [
				NotificationBuilder::new()
					->setTitle('Colonisation ratée')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('memorial', ['id' => $commander->id]),
							$commander->name,
						),
						' est tombé lors de l\'attaque de la planète rebelle située aux coordonnées ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							Game::formatCoord(
								$place->system->xPosition,
								$place->system->xPosition,
								$place->position,
								$place->system->sector->identifier
							),
						),
						'.',
						NotificationBuilder::divider(),
						'Il a désormais rejoint le Mémorial. Que son âme traverse l\'Univers dans la paix.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_archives', ['id' => $report->id]),
							'voir le rapport',
						),
					))
					->for($commander->player),
			],
			Planet::CONQUERPLAYERWHITOUTBATTLESUCCESS => [
				NotificationBuilder::new()
					->setTitle('Conquête réussie')
					->setContent(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' a conquis la planète non défendue ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						'.',
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_XP,
							'+ '.Format::number($commander->earnedExperience),
							'expérience de l\'officier',
						),
						'Elle est désormais votre, vous pouvez l\'administrer ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('switchplanet', ['planetId' => $place->id]),
							'ici',
						),
						'.',
					)
					->for($commander->player),
				NotificationBuilder::new()
					->setTitle('Planète conquise')
					->setContent(NotificationBuilder::paragraph(
						'L\'officier ',
						NotificationBuilder::bold($commander->name),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $commander->player]),
							$commander->player->name,
						),
						' a conquis votre planète non défendue ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'.',
						NotificationBuilder::divider(),
						'Impliquez votre faction dans une action punitive envers votre assaillant.',
					))
					->for($place->player),
			],
			Planet::CONQUERLOST => [
				NotificationBuilder::new()
					->setTitle('Erreur de coordonnées')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' n\'a pas attaqué la planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' car le joueur est dans votre faction, sous la protection débutant ou votre allié.',
					))
					->for($commander->player),
			],
			Planet::COMEBACK => [
				NotificationBuilder::new()
					->setTitle('Rapport de retour')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' est de retour sur votre base ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' et rapporte ',
						NotificationBuilder::bold(Format::number($commander->resources)),
						' ressources à vos entrepôts.'
					))
					->for($commander->player),
			],
			default => throw new \RuntimeException(sprintf('Unknown notification type %s', $case)),
		};

		foreach ($notifications as $notification) {
			$this->notificationRepository->save($notification);
		}
	}

	/**
	 * @param list<Uuid> $reports
	 */
	public function sendNotifForConquest(Planet $place, int $case, Commander $commander, array $reports = []): void
	{
		$nbrBattle = count($reports);

		$notifications = match ($case) {
			Planet::CONQUERPLAYERWHITBATTLESUCCESS => [
				NotificationBuilder::new()
					->setTitle('Conquête réussie')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('fleet_headquarters', ['commander' => $commander->id]),
							$commander->name,
						),
						' a conquis la planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						'.',
						NotificationBuilder::divider(),
						$nbrBattle.Format::addPlural($nbrBattle, ' combats ont eu lieu.', ' seul combat a eu lieu'),
						NotificationBuilder::divider(),
						NotificationBuilder::resourceBox(
							NotificationBuilder::RESOURCE_TYPE_XP,
							'+ '.Format::number($commander->earnedExperience),
							'expérience de l\'officier',
						),
						NotificationBuilder::divider(),
						'Elle est désormais vôtre, vous pouvez l\'administrer ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('switchplanet', ['planetId' => $place->id]),
							'ici',
						),
						'.',
						NotificationBuilder::divider(),
						...array_map(
							fn (int $i): string => sprintf(
								'%s%s',
								NotificationBuilder::divider(),
								NotificationBuilder::link(
									$this->urlGenerator->generate('fleet_archives', ['id' => $reports[$i]]),
									'voir le '.Format::ordinalNumber($i + 1).' rapport',
								),
							),
							array_keys($reports),
						),
					))
					->for($commander->player),
				NotificationBuilder::new()
					->setTitle('Planète conquise')
					->setContent(NotificationBuilder::paragraph(
						'L\'officier ',
						NotificationBuilder::bold($commander->name),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $commander->player]),
							$commander->player->name,
						),
						' a conquis votre planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'.',
						NotificationBuilder::divider(),
						$nbrBattle.Format::addPlural($nbrBattle, ' combats ont eu lieu.', ' seul combat a eu lieu'),
						NotificationBuilder::divider(),
						'Impliquez votre faction dans une action punitive envers votre assaillant.',
						NotificationBuilder::divider(),
						...array_map(
							fn (int $i): string => sprintf(
								'%s%s',
								NotificationBuilder::divider(),
								NotificationBuilder::link(
									$this->urlGenerator->generate('fleet_archives', ['id' => $reports[$i]]),
									'voir le '.Format::ordinalNumber($i + 1).' rapport',
								),
							),
							array_keys($reports),
						),
					))
					->for($place->player),
			],
			Planet::CONQUERPLAYERWHITBATTLEFAIL => [
				NotificationBuilder::new()
					->setTitle('Conquête ratée')
					->setContent(NotificationBuilder::paragraph(
						'Votre officier ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('memorial', ['id' => $commander->id]),
							$commander->name,
						),
						' est tombé lors de la tentive de conquête de la planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $place->player]),
							$place->player->name,
						),
						'.',
						NotificationBuilder::divider(),
						$nbrBattle.Format::addPlural($nbrBattle, ' combats ont eu lieu.', ' seul combat a eu lieu'),
						NotificationBuilder::divider(),
						'Il a désormais rejoint de Mémorial. Que son âme traverse l\'Univers dans la paix.',
						NotificationBuilder::divider(),
						...array_map(
							fn (int $i): string => sprintf(
								'%s%s',
								NotificationBuilder::divider(),
								NotificationBuilder::link(
									$this->urlGenerator->generate('fleet_archives', ['id' => $reports[$i]]),
									'voir le '.Format::ordinalNumber($i + 1).' rapport',
								),
							),
							array_keys($reports),
						),
					))
					->for($commander->player),
				NotificationBuilder::new()
					->setTitle('Rapport de combat')
					->setContent(NotificationBuilder::paragraph(
						'L\'officier ',
						NotificationBuilder::bold($commander->name),
						' appartenant au joueur ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $commander->player]),
							$commander->player->name,
						),
						' a tenté de conquérir votre planète ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $place->id]),
							$place->name,
						),
						'.',
						NotificationBuilder::divider(),
						$nbrBattle.Format::addPlural($nbrBattle, ' combats ont eu lieu.', ' seul combat a eu lieu'),
						NotificationBuilder::divider(),
						'Vous avez repoussé l\'ennemi avec succès. Bravo !',
						NotificationBuilder::divider(),
						...array_map(
							fn (int $i): string => sprintf(
								'%s%s',
								NotificationBuilder::divider(),
								NotificationBuilder::link(
									$this->urlGenerator->generate('fleet_archives', ['id' => $reports[$i]]),
									'voir le '.Format::ordinalNumber($i + 1).' rapport',
								),
							),
							array_keys($reports),
						),
					))
					->for($place->player),
			],
			default => throw new \RuntimeException(sprintf('Unknown notification type %s', $case)),
		};

		foreach ($notifications as $notification) {
			$this->notificationRepository->save($notification);
		}
	}
}
