<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Mission;

use App\Modules\Ares\Application\Battle\DescribeCommanderFleet;
use App\Modules\Ares\Application\Battle\GenerateRebelFleet;
use App\Modules\Ares\Application\Battle\Message\BattleEndedMessage;
use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Applies the outcome of a battle in orbit of a planet to the game (D15):
 * - every fleet keeps the ships it has left, and its officer earns experience (D19);
 * - a destroyed fleet loses its officer;
 * - a fleet that fled goes back to its base, abandoning its mission;
 * - the fleets of the winning side carry out their missions, in order of arrival (R7);
 * - the rebels of the planet keep a danger in proportion to their remaining strength (D16).
 */
#[AsMessageHandler]
readonly class ConcludePlanetBattleHandler
{
	public function __construct(
		private BattleRepositoryInterface $battleRepository,
		private CommanderRepositoryInterface $commanderRepository,
		private PlanetRepositoryInterface $planetRepository,
		private ComputeBattleExperience $computeBattleExperience,
		private CommanderExperienceHandler $commanderExperienceHandler,
		private AccomplishMission $accomplishMission,
		private MoveFleet $moveFleet,
		private BattleNotifier $battleNotifier,
		private EntityManagerInterface $entityManager,
		private LockFactory $lockFactory,
		private ClockInterface $clock,
	) {
	}

	public function __invoke(BattleEndedMessage $message): void
	{
		$battle = $this->battleRepository->get($message->battleId);

		if (null === $battle || null === ($planet = $battle->place) || !$battle->isOver()) {
			return;
		}

		$lock = $this->lockFactory->createLock(sprintf('planet-battle-%s', $planet->id->toRfc4122()), ttl: 60);
		$lock->acquire(true);

		try {
			$this->conclude($battle, $planet);
		} finally {
			$lock->release();
		}
	}

	private function conclude(Battle $battle, Planet $planet): void
	{
		$state = $battle->getState();
		$commanders = [];
		foreach ($this->commanderRepository->getBattleCommanders($battle) as $commander) {
			$commanders[DescribeCommanderFleet::getIdentifier($commander)] = $commander;
		}

		// Already concluded (the message was delivered twice)
		if ([] === $commanders) {
			return;
		}

		$experience = ($this->computeBattleExperience)($state);
		$outcomes = [];
		$sides = [];
		$winners = [];

		foreach ($state->fleets as $fleet) {
			if (str_starts_with($fleet->identifier, GenerateRebelFleet::IDENTIFIER_PREFIX)) {
				$this->updateDanger($planet, $fleet);

				continue;
			}

			if (null === ($commander = $commanders[$fleet->identifier] ?? null)) {
				continue;
			}

			$sides[$fleet->identifier] = $fleet->side;
			$commander->battle = null;
			$this->applyLosses($commander, $fleet);
			$this->commanderExperienceHandler->awardBattleExperience($commander, $experience[$fleet->identifier] ?? 0);

			$outcome = $this->applyFate($commander, $fleet, $state, $planet);
			if (null === $outcome) {
				$winners[] = $commander;
				$outcome = $commander->travelType instanceof CommanderMission ? 'victorieux, poursuit sa mission' : 'victorieux';
			}
			$outcomes[] = [$commander, $outcome];
		}

		$this->entityManager->flush();
		$this->battleNotifier->notifyEnd($battle, $outcomes, $state->winningSide, $sides);

		// The winners carry out their missions once every fleet is settled, e.g. a conquest after the defenders died
		foreach ($winners as $commander) {
			if ($commander->travelType instanceof CommanderMission) {
				($this->accomplishMission)($commander, $battle);
			}
		}

		$this->entityManager->flush();
	}

	/**
	 * Squadrons are matched with the battle squadrons by their order in the formation given to the battle.
	 */
	private function applyLosses(Commander $commander, BattleFleet $fleet): void
	{
		if (!$fleet->isDeployed()) {
			return;
		}

		$remainingShips = [];
		foreach ($fleet->squadrons as $squadron) {
			$remainingShips[$squadron->identifier] = $squadron->ships;
		}

		foreach (DescribeCommanderFleet::getSquadrons($commander) as $index => $squadron) {
			$squadron->ships = $remainingShips[sprintf('%s-%d', $fleet->identifier, $index)] ?? $squadron->ships;
			$squadron->updatedAt = $this->clock->now();

			if (0 === $squadron->ships) {
				$commander->squadrons->removeElement($squadron);
			}
		}
	}

	/**
	 * @return string|null what became of the officer, null for a winner still in orbit
	 */
	private function applyFate(Commander $commander, BattleFleet $fleet, BattleState $state, Planet $planet): ?string
	{
		$isWinner = null !== $state->winningSide && $fleet->side === $state->winningSide;

		if ($isWinner) {
			++$commander->palmares;
			if (null !== $commander->player) {
				++$commander->player->victory;
			}
		}

		if ($fleet->isDestroyed()) {
			$commander->statement = Commander::DEAD;
			$commander->diedAt = $this->clock->now();
			$commander->squadrons->clear();
			if (null !== $commander->player) {
				++$commander->player->defeat;
			}

			return 'flotte détruite, l’officier a rejoint le Mémorial';
		}

		if ($fleet->hasFled() || !$isWinner) {
			if (!$isWinner && null !== $commander->player) {
				++$commander->player->defeat;
			}

			$this->leave($commander, $planet);

			return $fleet->hasFled() ? 'a pris la fuite avec ses vaisseaux restants' : 'se retire sans vainqueur';
		}

		if (!$commander->travelType instanceof CommanderMission) {
			$commander->statement = Commander::AFFECTED;
		}

		return null;
	}

	/**
	 * A fleet leaving the battle goes back to its base, or stays there when it defended it.
	 */
	private function leave(Commander $commander, Planet $planet): void
	{
		if (!$commander->travelType instanceof CommanderMission || $commander->base === $planet) {
			$commander->travelType = null;
			$commander->statement = Commander::AFFECTED;

			return;
		}

		$commander->resources = 0;
		// The way back starts at the end of the battle
		$commander->arrivedAt = $this->clock->now();
		($this->moveFleet)(
			commander: $commander,
			origin: $planet,
			destination: $commander->base ?? $commander->startPlace,
			mission: CommanderMission::Back,
		);
	}

	/**
	 * Legacy formula: the danger falls in proportion to the strength the rebels lost.
	 */
	private function updateDanger(Planet $planet, BattleFleet $rebels): void
	{
		$planet->danger = intval(round($planet->danger * ($rebels->getPev() + 1) / ($rebels->getFormationPev() + 1)));
		$this->planetRepository->save($planet);
	}
}
