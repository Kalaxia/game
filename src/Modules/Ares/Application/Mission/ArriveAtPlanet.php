<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Mission;

use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use Symfony\Component\Lock\LockFactory;

/**
 * A fleet reaches the end of its travel (Phase 7):
 * - while a battle goes on in orbit, it joins it if it is hostile to one of the sides (Q3, Q9);
 * - when it attacks a defended planet, a battle starts (D14, D16);
 * - otherwise, it carries out its mission at once.
 *
 * Arrivals at a planet are serialised, so that two attackers arriving together end up in the same battle.
 */
readonly class ArriveAtPlanet
{
	public function __construct(
		private BattleRepositoryInterface $battleRepository,
		private PlanetBattle $planetBattle,
		private AccomplishMission $accomplishMission,
		private LockFactory $lockFactory,
	) {
	}

	public function __invoke(Commander $commander): void
	{
		$planet = $commander->destinationPlace;
		$lock = $this->lockFactory->createLock(sprintf('planet-battle-%s', $planet->id->toRfc4122()), ttl: 30);
		$lock->acquire(true);

		try {
			$battle = $this->battleRepository->getRunningBattleAt($planet);

			if (null !== $battle && $this->planetBattle->join($battle, $commander)) {
				return;
			}

			if (AccomplishMission::isAttack($commander) && null !== ($defenders = $this->planetBattle->getDefenders($planet))) {
				$this->planetBattle->start($planet, $commander, $defenders);

				return;
			}

			($this->accomplishMission)($commander);
		} finally {
			$lock->release();
		}
	}
}
