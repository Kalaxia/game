<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Mission;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\DescribeCommanderFleet;
use App\Modules\Ares\Application\Battle\GenerateRebelFleet;
use App\Modules\Ares\Application\Battle\JoinBattle;
use App\Modules\Ares\Application\Battle\StartBattle;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\FactionHostilityInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Galaxy\Domain\Entity\Planet;

/**
 * Battles in orbit of a planet: started by an attack (D14), joined by the fleets arriving
 * while it goes on (Q3, Q9).
 *
 * The defenders are side 0: the officers of the planet owner, or the rebels of a planet without owner (D16).
 */
readonly class PlanetBattle
{
	public const int DEFENDING_SIDE = 0;

	public function __construct(
		private CreateBattle $createBattle,
		private StartBattle $startBattle,
		private JoinBattle $joinBattle,
		private DescribeCommanderFleet $describeCommanderFleet,
		private GenerateRebelFleet $generateRebelFleet,
		private FactionHostilityInterface $factionHostility,
		private CommanderRepositoryInterface $commanderRepository,
		private BattleNotifier $battleNotifier,
	) {
	}

	/**
	 * Fleets defending the planet, if any: the officers of its owner in orbit with ships, or its rebels.
	 *
	 * @return list<Commander>|null the officers, an empty list for rebels, or null when nobody defends the planet
	 */
	public function getDefenders(Planet $planet): ?array
	{
		if (null === $planet->player) {
			return $this->generateRebelFleet->getPev($planet) > 0 ? [] : null;
		}

		$defenders = array_values(array_filter(
			$this->commanderRepository->getPlanetCommanders($planet, [Commander::AFFECTED]),
			fn (Commander $commander) => $commander->player?->id === $planet->player->id && $commander->hasShips(),
		));

		return [] !== $defenders ? $defenders : null;
	}

	/**
	 * @param list<Commander> $defenders officers of the planet owner, none for the rebels
	 */
	public function start(Planet $planet, Commander $attacker, array $defenders): Battle
	{
		$fleets = [] === $defenders
			? [$this->generateRebelFleet->describe($planet, self::DEFENDING_SIDE)]
			: array_map(fn (Commander $defender) => ($this->describeCommanderFleet)($defender, self::DEFENDING_SIDE), $defenders);
		$fleets[] = ($this->describeCommanderFleet)($attacker, self::DEFENDING_SIDE + 1);

		$battle = ($this->startBattle)(($this->createBattle)($fleets, random_int(1, 1_000_000)), $planet);

		if (null !== $planet->player) {
			$battle->addPlayer($planet->player);
		}
		foreach ([...$defenders, $attacker] as $commander) {
			$this->engage($commander, $battle);
		}
		$this->battleNotifier->notifyStart($battle, [...$defenders, $attacker]);

		return $battle;
	}

	/**
	 * Brings an arriving fleet into the battle when it is hostile to one of the sides (Q2, Q3).
	 *
	 * @return bool false when the fleet stays out of the battle
	 */
	public function join(Battle $battle, Commander $commander): bool
	{
		if (null === ($side = $this->findSide($battle, $commander))) {
			return false;
		}

		($this->joinBattle)($battle->id, ($this->describeCommanderFleet)($commander, $side));
		$this->engage($commander, $battle);
		$this->battleNotifier->notifyJoin($battle, $commander);

		return true;
	}

	/**
	 * The side of its faction if there is one; otherwise the first side it is not hostile to,
	 * a new side if it is hostile to all of them, or none if it is hostile to none.
	 */
	private function findSide(Battle $battle, Commander $commander): ?int
	{
		$faction = $commander->player->faction->identifier;
		$sides = [];

		foreach ($battle->getState()->fleets as $fleet) {
			$sides[$fleet->side][] = $fleet;
		}
		ksort($sides);

		$firstFriendlySide = null;
		$isHostileToAnySide = false;

		foreach ($sides as $side => $fleets) {
			if ([] !== array_filter($fleets, fn (BattleFleet $fleet) => $fleet->faction === $faction)) {
				return $side;
			}

			$isHostile = [] !== array_filter($fleets, fn (BattleFleet $fleet) => $this->isHostile($commander, $battle->place, $fleet->faction));
			$isHostileToAnySide = $isHostileToAnySide || $isHostile;

			if (!$isHostile) {
				$firstFriendlySide ??= $side;
			}
		}

		return match (true) {
			!$isHostileToAnySide => null,
			null !== $firstFriendlySide => $firstFriendlySide,
			default => max(array_keys($sides)) + 1,
		};
	}

	/**
	 * Hostility of the factions (R1), plus the target designated by the mission of the fleet (D14).
	 */
	private function isHostile(Commander $commander, Planet $planet, int $faction): bool
	{
		$isTargetOfMission = AccomplishMission::isAttack($commander)
			&& $commander->destinationPlace === $planet
			&& $faction === ($planet->player?->faction->identifier ?? ColorResource::NO_FACTION);

		return $isTargetOfMission || $this->factionHostility->areEnemies($commander->player->faction->identifier, $faction);
	}

	private function engage(Commander $commander, Battle $battle): void
	{
		$commander->statement = Commander::IN_BATTLE;
		$commander->battle = $battle;
		$battle->addPlayer($commander->player);
		$this->commanderRepository->save($commander);
	}
}
