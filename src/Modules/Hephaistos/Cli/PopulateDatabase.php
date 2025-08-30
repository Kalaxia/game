<?php

namespace App\Modules\Hephaistos\Cli;

use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Model\CommercialTax;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Helper\GalaxyGenerator;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationUserRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
	name: 'app:hephaistos:populate-database',
	description: 'fill the database and set it to ready state for a new game',
)]
class PopulateDatabase extends Command
{
	public function __construct(
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly GalaxyGenerator $galaxyGenerator,
		private readonly CommercialTaxRepositoryInterface $commercialTaxRepository,
		private readonly ConversationRepositoryInterface $conversationRepository,
		private readonly ConversationUserRepositoryInterface $conversationUserRepository,
		private readonly GetFactionsConfiguration $getFactionsConfiguration,
		private readonly PlayerRepositoryInterface $playerRepository,
		private readonly TransactionRepositoryInterface $transactionRepository,
		#[Autowire('%game.available_factions%')]
		private readonly array $availableFactions,
	) {
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Factions creation');
		$date = new \DateTimeImmutable('-500000 seconds');

		$rebelFaction = new Color(
			id: Uuid::v4(),
			identifier: ColorResource::NO_FACTION,
			isClosed: true,
			isInGame: true,
			lastElectionHeldAt: $date,
		);

		$this->colorRepository->save($rebelFaction);

		$relations = array_reduce(
			$this->availableFactions,
			fn (array $relations, int $factionId) => array_merge($relations, [$factionId => Color::NEUTRAL]),
			[],
		);

		foreach ($this->availableFactions as $factionId) {
			$faction = new Color(
				Uuid::v4(),
				identifier: $factionId,
				alive: true,
				electionStatement: Color::MANDATE,
				regime: ($this->getFactionsConfiguration)($factionId, 'regime'),
				isInGame: true,
				relations: array_filter(
					$relations,
					fn (int $identifier) => $identifier !== $factionId,
					ARRAY_FILTER_USE_KEY,
				),
				lastElectionHeldAt: $date,
			);

			$this->colorRepository->save($faction);
		}

		// --------------------------------------------------------------------------------------------
		$output->writeln('Ajout du Joueur Gaia');

		$p = new Player();
		$p->status = Player::STANDARD;
		$p->credit = 10000000;
		$p->experience = 15000;
		$p->factionPoint = 0;
		$p->level = 5;
		$p->victory = 0;
		$p->defeat = 0;
		$p->stepTutorial = 0;
		$p->stepDone = false;
		$p->iUniversity = 0;
		$p->partNaturalSciences = 25;
		$p->partLifeSciences = 25;
		$p->partSocialPoliticalSciences = 25;
		$p->partInformaticEngineering = 25;
		$p->uPlayer = new \DateTimeImmutable();
		$p->dInscription = new \DateTimeImmutable();
		$p->dLastConnection = new \DateTimeImmutable();
		$p->dLastActivity = new \DateTimeImmutable();
		$p->premium = false;
		$p->statement = Player::DEAD;

		// @TODO remove Rebel player
		$rebelPlayer = clone $p;
		$rebelPlayer->id = 1;
		$rebelPlayer->bind = Utils::generateString(25);
		$rebelPlayer->name = 'Rebelle';
		$rebelPlayer->avatar = 'rebel';
		$this->playerRepository->save($rebelPlayer);

		// @TODO remove Assistant player
		$assistantPlayer = clone $p;
		$assistantPlayer->id = 2;
		$assistantPlayer->name = 'Jean-Mi';
		$assistantPlayer->avatar = 'jm';
		$this->playerRepository->save($assistantPlayer);

		// @TODO remove faction players
		foreach ($this->availableFactions as $factionId) {
			$p = clone $p;
			$p->bind = Utils::generateString(25);
			$p->name = ($this->getFactionsConfiguration)($factionId, 'officialName');
			$p->avatar = ('color-'.$factionId);
			$p->faction = $this->colorRepository->getOneByIdentifier($factionId);
			$p->status = 6;
			$this->playerRepository->save($p);
		}

		$output->writeln('Remplissage de la table transaction');

		$transactionData = [
			[Transaction::TYP_RESOURCE, 8, 10, 1.26],
			[Transaction::TYP_COMMANDER, 1, 12, 12],
			[Transaction::TYP_SHIP, 8, 15, 1.875],
		];

		foreach ($transactionData as [$type, $quantity, $price, $currentRate]) {
			// @TODO forbid nullable bases for transactions when these are no longer required to make the transaction system work.
			$transaction = new Transaction(
				id: Uuid::v4(),
				player: $rebelPlayer,
				base: null,
				type: $type,
				quantity: $quantity,
				identifier: 0,
				publishedAt: new \DateTimeImmutable(),
				currentRate: $currentRate,
				price: $price,
				statement: Transaction::ST_COMPLETED,
				validatedAt: new \DateTimeImmutable(),
			);

			$this->transactionRepository->save($transaction);
		}

		$output->writeln('Remplissage de la table commercialTax');

		// génération des taxes
		foreach ($this->availableFactions as $factionId) {
			$faction = $this->colorRepository->getOneByIdentifier($factionId)
				?? throw new \InvalidArgumentException('Faction not found');

			foreach ($this->availableFactions as $rfactionId) {
				$relatedFaction = $this->colorRepository->getOneByIdentifier($rfactionId)
					?? throw new \InvalidArgumentException('Related faction not found');

				$commercialTax = new CommercialTax(
					id: Uuid::v4(),
					faction: $faction,
					relatedFaction: $relatedFaction,
					exportTax: 5,
					importTax: 5,
				);

				$this->commercialTaxRepository->save($commercialTax);
			}
		}

		$output->writeln('Remplissage des conversations');

		// conv jeanmi
		$conv = new Conversation(
			id: Uuid::v4(),
			title: 'Jean-Mi, administrateur système',
			messagesCount: 0,
			createdAt: new \DateTimeImmutable(),
			lastMessageAt: new \DateTimeImmutable(),
			type: Conversation::TY_SYSTEM,
		);
		$this->conversationRepository->save($conv);

		$user = new ConversationUser(
			id: Uuid::v4(),
			conversation: $conv,
			player: $assistantPlayer,
			lastViewedAt: new \DateTimeImmutable(),
			playerStatus: ConversationUser::US_ADMIN,
			conversationStatus: ConversationUser::CS_DISPLAY,
		);
		$this->conversationUserRepository->save($user);

		foreach ($this->availableFactions as $factionId) {
			$faction = $this->colorRepository->getOneByIdentifier($factionId);
			$player = $this->playerRepository->getFactionAccount($faction);

			$conv = new Conversation(
				id: Uuid::v4(),
				title: 'Communication de '.($this->getFactionsConfiguration)($player->faction, 'popularName'),
				messagesCount: 0,
				createdAt: new \DateTimeImmutable(),
				lastMessageAt: new \DateTimeImmutable(),
				type: Conversation::TY_SYSTEM,
			);
			$this->conversationRepository->save($conv);

			$user = new ConversationUser(
				id: Uuid::v4(),
				conversation: $conv,
				player: $player,
				lastViewedAt: new \DateTimeImmutable(),
				playerStatus: ConversationUser::US_ADMIN,
				conversationStatus: ConversationUser::CS_DISPLAY,
			);
			$this->conversationUserRepository->save($user);
		}

		$output->writeln('Génération de la galaxie');

		$this->galaxyGenerator->generate();

		return self::SUCCESS;
	}
}
