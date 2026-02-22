<?php

declare(strict_types=1);

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Message\CommanderSchoolExperienceMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class CommanderSchoolExperienceHandler
{
	private const int MAX_UPDATES = 20;

	public function __construct(
		private GameTimeConverter $gameTimeConverter,
		private EntityManagerInterface $entityManager,
		private CommanderRepositoryInterface $commanderRepository,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
		private MessageBusInterface $messageBus,
		private LoggerInterface $logger,
		private PlayerBonusManager $playerBonusManager,
		private CommanderExperienceHandler $commanderExperienceHandler,
	) {
	}

	public function __invoke(CommanderSchoolExperienceMessage $message): void
	{
		$commander = $this->commanderRepository->get($message->commanderId)
			?? throw new \RuntimeException(sprintf('Commander %s not found', $message->commanderId));

		// If the commander was updated recently, we skip him
		$missingUpdatesCount = ($this->countMissingSystemUpdates)($commander);
		if (0 === $missingUpdatesCount) {
			return;
		}

		$planet = $commander->base;

		$playerBonus = $this->playerBonusManager->getBonusByPlayer($commander->player);
		$playerBonus = $playerBonus->bonuses;

		$secondsPerGameCycle = $this->gameTimeConverter->convertGameCyclesToSeconds(1);

		try {
			$this->entityManager->beginTransaction();

			$launchNewMessage = false;

			for ($i = 0; $i < $missingUpdatesCount; ++$i) {
				if (self::MAX_UPDATES === $i) {
					$launchNewMessage = true;

					break;
				}
				$invest = $planet->iSchool;
				$invest += $invest * $playerBonus->get(PlayerBonusId::COMMANDER_INVEST) / 100;

				// xp gagnée
				// TODO Factorize in a service and check potential duplicates
				$earnedExperience = $invest / Commander::COEFFSCHOOL;
				$earnedExperience += (1 == random_int(0, 1))
					? random_int(0, intval(round($earnedExperience / 20)))
					: -(random_int(0, intval(round($earnedExperience / 20))));
				$earnedExperience = max(intval(round($earnedExperience)), 0);

				$this->commanderExperienceHandler->upExperience($commander, $earnedExperience);

				$commander->updatedAt = $commander->updatedAt->modify(sprintf('+%d seconds', $secondsPerGameCycle));
			}

			$this->entityManager->commit();

			if (true === $launchNewMessage) {
				$this->messageBus->dispatch(new CommanderSchoolExperienceMessage($commander->id));

				$this->logger->debug('Dispatched new school experience update message for the next iterations for commander {commanderName} of player {playerName}', [
					'commanderName' => $commander->name,
					'commanderId' => $commander->id,
					'playerName' => $commander->player->name,
					'playerId' => $commander->player->id,
				]);
			}
		} catch (\Throwable $e) {
			$this->entityManager->rollback();

			throw $e;
		}
	}
}
