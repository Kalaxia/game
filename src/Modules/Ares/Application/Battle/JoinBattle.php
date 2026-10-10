<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Brings a fleet into a running battle: it is deployed at the next turn (Q9).
 */
readonly class JoinBattle
{
	public function __construct(
		private BattleRepositoryInterface $battleRepository,
		private CreateBattle $createBattle,
		private BattleLock $battleLock,
	) {
	}

	/**
	 * @param array<string, mixed> $fleet described as for CreateBattle; its identifier must be new in the battle
	 *
	 * @throws \DomainException when the battle is over or unknown, or the fleet is invalid
	 */
	public function __invoke(Uuid $battleId, array $fleet): BattleFleet
	{
		return $this->battleLock->run($battleId, function () use ($battleId, $fleet): BattleFleet {
			$battle = $this->battleRepository->get($battleId) ?? throw new \DomainException('Unknown battle');
			// Another process may have resolved a turn since the battle was loaded
			$this->battleRepository->refresh($battle);

			if ($battle->isOver()) {
				throw new \DomainException('The battle is over');
			}

			$state = $battle->getState();
			$identifier = $fleet['identifier'] ?? sprintf('fleet-%d', count($state->fleets));
			foreach ($state->fleets as $existing) {
				if ($existing->identifier === $identifier) {
					throw new \DomainException(sprintf('Fleet %s is already in the battle', $identifier));
				}
			}

			$battleFleet = $this->createBattle->createFleet(['identifier' => $identifier, 'arrival_turn' => $state->turn + 1] + $fleet, count($state->fleets));
			$state->addFleet($battleFleet);
			$battle->setState($state);
			$this->battleRepository->save($battle);

			return $battleFleet;
		});
	}
}
