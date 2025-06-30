<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Command;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Resource\ColorResource;
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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
	name: 'app:hermes:synchronize-player-conversations',
	description: 'Create missing conversation users for faction and system conversations'
)]
class SynchronizePlayerConversations extends Command
{
	public function __construct(
		private readonly ColorRepositoryInterface $factionRepository,
		private readonly ConversationRepositoryInterface $conversationRepository,
		private readonly ConversationUserRepositoryInterface $conversationUserRepository,
		private readonly GetFactionsConfiguration $getFactionsConfiguration,
		private readonly PlayerRepositoryInterface $playerRepository,
		#[Autowire('%id_jeanmi%')]
		private readonly int $jeanMiId,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		$factions = $this->factionRepository->getInGameFactions();

		foreach ($factions as $faction) {
			$factionName = ($this->getFactionsConfiguration)($faction, 'officialName');
			$style->info(sprintf('Processing faction conversation : %s', $factionName));

			$factionAccount = $this->playerRepository->getFactionAccount($faction)
				?? throw new \RuntimeException(sprintf('Faction account not found for %s', $factionName));
			$factionConversation = $this->conversationRepository->getOneByPlayer($factionAccount);

			$players = $this->playerRepository->getFactionPlayersByName($faction);

			$this->includeMissingPlayers($factionConversation, $players, $style);
		}

		$style->info('Processing system conversation');

		$systemPlayer = $this->playerRepository->get($this->jeanMiId)
			?? throw new \RuntimeException('Missing system Player');
		$systemConversation = $this->conversationRepository->getOneByPlayer($systemPlayer);

		$this->includeMissingPlayers($systemConversation, $this->playerRepository->getActivePlayers(), $style);

		return self::SUCCESS;
	}

	/**
	 * @param list<Player> $players
	 */
	private function includeMissingPlayers(Conversation $conversation, array $players, SymfonyStyle $style): void
	{
		$missingPlayersCount = 0;
		$includedPlayersIds = $conversation->players->map(
			fn (ConversationUser $conversationUser) => $conversationUser->player->id
		);

		foreach ($players as $player) {
			if ($includedPlayersIds->contains($player->id)) {
				continue;
			}
			$missingPlayersCount++;

			$conversationUser = new ConversationUser(
				id: Uuid::v4(),
				conversation: $conversation,
				player: $player,
				lastViewedAt: new \DateTimeImmutable(),
				conversationStatus: ConversationUser::CS_ARCHIVED,
			);

			$this->conversationUserRepository->save($conversationUser);
		}

		if ($missingPlayersCount > 0) {
			$style->success(sprintf('Successfully added %d players in conversation !', $missingPlayersCount));
		} else {
			$style->info(sprintf('Conversation already included all %d players !', $includedPlayersIds->count()));
		}
	}
}
