<?php

namespace App\Modules\Zeus\Application\Factory;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Service\UpdatePlanetPoints;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Infrastructure\Repository\Doctrine\PlanetRepository;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationUserRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Promethee\Model\Research;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

readonly class PlayerFactory
{
	public function __construct(
		private EntityManagerInterface              $entityManager,
		private ConversationRepositoryInterface     $conversationRepository,
		private ConversationUserRepositoryInterface $conversationUserRepository,
		private EventDispatcherInterface            $eventDispatcher,
		private PlayerManager                       $playerManager,
		private PlayerRepositoryInterface           $playerRepository,
		private NotificationRepositoryInterface     $notificationRepository,
		private PlanetRepository                    $planetRepository,
		private UpdatePlanetPoints                  $updatePlanetPoints,
		private ResearchHelper                      $researchHelper,
		private ResearchRepositoryInterface         $researchRepository,
		private PlaceRepositoryInterface            $placeRepository,
		private PlaceManager                        $placeManager,
		private TechnologyRepositoryInterface       $technologyRepository,
		private UrlGeneratorInterface               $urlGenerator,
		#[Autowire('%id_jeanmi%')]
		private int                                 $jeanMiId,
	) {
	}

	public function create(
		Color $faction,
		User|null $user,
		string $name,
		string $avatar,
		Sector $sector,
		string $baseName,
		Player|null $godFather = null,
		bool $highMode = false,
	): Player {
		$this->entityManager->beginTransaction();

		// AJOUT DU JOUEUR EN BASE DE DONNEE
		$player = new Player();

		// ajout des variables inchangées
		$player->user = $user;
		$player->faction = $faction;
		$player->name = $name;
		$player->avatar = $avatar;
		$player->godFather = $godFather;
		$player->status = Player::STANDARD;
		$player->uPlayer = new \DateTimeImmutable();

		$player->victory = 0;
		$player->defeat = 0;

		$player->stepTutorial = 1;
		$player->stepDone = true;

		$player->iUniversity = 1000;
		$player->partNaturalSciences = 25;
		$player->partLifeSciences = 25;
		$player->partSocialPoliticalSciences = 25;
		$player->partInformaticEngineering = 25;
		// @TODO adapt this vlaue depending on the chosen avatar or player's choice
		$player->sex = 1;

		$player->dInscription = new \DateTimeImmutable();
		$player->dLastConnection = new \DateTimeImmutable();
		$player->dLastActivity = new \DateTimeImmutable();

		$player->premium = false;
		$player->statement = Player::ACTIVE;

		// ajout des variables dépendantes
		if ($highMode) {
			$player->credit = 10000000;
			$player->experience = 18000;
			$player->level = 5;
		} else {
			$player->credit = 5000;
			$player->experience = 630;
			$player->level = 1;
		}

		$this->playerRepository->save($player);

		if (null !== $godFather) {
			$n = NotificationBuilder::new()
				->setTitle('Votre filleul s\'est inscrit')
				->setContent(
					NotificationBuilder::paragraph(
						'Un de vos amis a créé un compte.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->urlGenerator->generate('embassy', ['player' => $player->id]),
							$player->name,
						),
						'.',
					),
					NotificationBuilder::paragraph(
						'Vous venez de gagner 1000 crédits. Vous en gagnerez 1 million de plus lorsqu\'il atteindra le niveau 3.',
					),
				)
				->for($player->godFather);

			$this->notificationRepository->save($n);

			// add 1000 credits to the godfather
			$this->playerManager->increaseCredit($godFather, 1000);
		}

		// INITIALISATION DES RECHERCHES
		// rendre aléatoire
		$rs = new Research(
			Uuid::v4(),
			player: $player,
			naturalToPay: $this->researchHelper->getInfo(Research::MATH, 'level', 1, 'price'),
			lifeToPay: $this->researchHelper->getInfo(Research::LAW, 'level', 1, 'price'),
			socialToPay: $this->researchHelper->getInfo(Research::ECONO, 'level', 1, 'price'),
			informaticToPay: $this->researchHelper->getInfo(Research::NETWORK, 'level', 1, 'price'),
			naturalTech: Research::MATH,
			lifeTech: Research::LAW,
			socialTech: Research::ECONO,
			informaticTech: Research::NETWORK,
		);

		if ($highMode) {
			$rs->mathLevel = 15;
			$rs->physLevel = 15;
			$rs->chemLevel = 15;
			$rs->bioLevel = 15;
			$rs->mediLevel = 15;
			$rs->econoLevel = 15;
			$rs->psychoLevel = 15;
			$rs->networkLevel = 15;
			$rs->algoLevel = 15;
			$rs->statLevel = 15;
		}

		$this->researchRepository->save($rs);

		// choix de la place
		$candidatePlaces = $this->planetRepository->getCandidatePlanetsForNewPlayers($sector);

		$placeId = $candidatePlaces[random_int(0, count($candidatePlaces) - 1)];
		$planet = $this->planetRepository->get($placeId);

		$planet->player = $player;
		$planet->name = $baseName;

		// création des premiers bâtiments
		if ($highMode) {
			// batiments haut-level
			$planet->levelGenerator = 35;
			$planet->levelRefinery = 35;
			$planet->levelDock1 = 35;
			$planet->levelDock2 = 10;
			$planet->levelDock3 = 0;
			$planet->levelTechnosphere = 35;
			$planet->levelCommercialPlateforme = 10;
			$planet->levelStorage = 35;
			$planet->levelRecycling = 15;
			$planet->levelSpatioport = 10;
			$planet->resourcesStorage = 3000000;

			// remplir le dock
			$planet->addShips(ShipCategory::LightFighter, 50);
			$planet->addShips(ShipCategory::Fighter, 50);
			$planet->addShips(ShipCategory::HeavyFighter, 10);
			$planet->addShips(ShipCategory::LightCorvette, 10);
			$planet->addShips(ShipCategory::Corvette, 5);
			$planet->addShips(ShipCategory::HeavyCorvette, 5);
			$planet->addShips(ShipCategory::LightFrigate, 2);
			$planet->addShips(ShipCategory::Frigate, 2);
			$planet->addShips(ShipCategory::Destroyer, 1);
			$planet->addShips(ShipCategory::HeavyDestroyer, 1);
			$planet->addShips(ShipCategory::Cruiser, 0);
			$planet->addShips(ShipCategory::HeavyCruiser, 0);
		} else {
			$planet->levelGenerator = 1;
			$planet->levelRefinery = 1;
			$planet->levelDock1 = 0;
			$planet->levelDock2 = 0;
			$planet->levelDock3 = 0;
			$planet->levelTechnosphere = 0;
			$planet->levelCommercialPlateforme = 0;
			$planet->levelStorage = 1;
			$planet->levelRecycling = 0;
			$planet->levelSpatioport = 0;
			$planet->resourcesStorage = 1000;
		}

		$this->updatePlanetPoints->updatePoints($planet);

		// initialisation des investissement
		$planet->iSchool = 500;
		$planet->iAntiSpy = 500;

		// ajout de la base
		$planet->updatedAt = new \DateTimeImmutable();
		$planet->createdAt = new \DateTimeImmutable();

		$this->createPlayerTechnology($player, $highMode);

		$this->planetRepository->save($planet);

		$this->placeManager->turnAsSpawnPlace($planet);

		$this->entityManager->commit();

		$this->eventDispatcher->dispatch(new PlaceOwnerChangeEvent($planet));

		// modification de la place

		// confirmation au portail
		// TODO Replace when portal is implemented
		/*if ('enabled' === $this->getParameter('apimode')) {
			$return = $this->api->confirmInscription($session->get('inscription')->get('bindkey'));
		}*/
		// clear les sessions

		// ajout aux conversation de faction et techniques
		if (($factionAccount = $this->playerRepository->getFactionAccount($player->faction)) !== null) {
			$this->addPlayerToConversation($player, $factionAccount);
		}
		if (($systemAccount = $this->playerRepository->get($this->jeanMiId)) !== null) {
			$this->addPlayerToConversation($player, $systemAccount);
		}

		return $player;
	}

	private function addPlayerToConversation(Player $registeringPlayer, Player $conversationOwner): void
	{
		$conversation = $this->conversationRepository->getOneByPlayer($conversationOwner);

		$playerConversation = new ConversationUser(
			id: Uuid::v4(),
			conversation: $conversation,
			player: $registeringPlayer,
			lastViewedAt: new \DateTimeImmutable(),
			playerStatus: ConversationUser::US_STANDARD,
			conversationStatus: ConversationUser::CS_ARCHIVED,
		);

		$this->conversationUserRepository->save($playerConversation);
	}

	private function createPlayerTechnology(Player $player, bool $isHighLevel): void
	{
		$technology = ($isHighLevel) ? new Technology(
			id: Uuid::v4(),
			player: $player,
			comPlatUnblock: 1,
			dock2Unblock: 1,
			dock3Unblock: 1,
			recyclingUnblock: 1,
			spatioportUnblock: 1,
			ship0Unblock: 1,
			ship1Unblock: 1,
			ship2Unblock: 1,
			ship3Unblock: 1,
			ship4Unblock: 1,
			ship5Unblock: 1,
			ship6Unblock: 1,
			ship7Unblock: 1,
			ship8Unblock: 1,
			ship9Unblock: 1,
			colonization: 1,
			conquest: 1,
			baseQuantity: 4,
		) : new Technology(id: Uuid::v4(), player: $player);

		$this->technologyRepository->save($technology);
	}
}
