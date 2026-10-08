<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\FleetStatus;
use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Model\Battle\OfficerTraitEffects;
use App\Modules\Ares\Domain\Model\Battle\Rules\BattleRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\CommandRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\EngagementRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\MoraleRules;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Domain\Service\Battle\Engine\ResolveFire;
use App\Modules\Ares\Domain\Service\Battle\Engine\SelectTarget;
use App\Tests\Modules\Ares\Domain\Service\Battle\BattleTestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Officer traits (Q13): one test per effect, plus the effects observed in fire and targeting.
 */
class OfficerTraitEffectsTest extends BattleTestCase
{
	public function testAnOfficerHasAtMostThreeTraits(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		self::officer([OfficerTrait::Aggressive, OfficerTrait::Tenacious, OfficerTrait::Veteran, OfficerTrait::Defender]);
	}

	public function testInitiative(): void
	{
		static::assertSame(1.1, OfficerTraitEffects::getInitiativeFactor(self::officer([OfficerTrait::Aggressive])));
		static::assertSame(0.85, OfficerTraitEffects::getInitiativeFactor(self::officer([OfficerTrait::Indecisive])));
		static::assertSame(1.0, OfficerTraitEffects::getInitiativeFactor(self::officer([])));
	}

	public function testLogisticianCommandsMore(): void
	{
		$rules = new CommandRules(basePev: 300, pevPerLevel: 100);
		$logistician = self::officer([OfficerTrait::Logistician]);

		// 480 PEV: over the 400 PEV of a level-1 officer, within the 480 of a logistician
		static::assertGreaterThan(0.0, $rules->getMalus(480, 1));
		static::assertSame(0.0, $rules->getMalus(480, 1, OfficerTraitEffects::getCommandCapacityFactor($logistician)));
	}

	public function testMoraleThresholds(): void
	{
		$rules = new MoraleRules();

		static::assertSame(20.0, OfficerTraitEffects::getWithdrawalThreshold(self::officer([OfficerTrait::Aggressive]), $rules));
		static::assertSame(40.0, OfficerTraitEffects::getWithdrawalThreshold(self::officer([OfficerTrait::Cautious]), $rules));
		static::assertSame(60.0, OfficerTraitEffects::getRemobilisationThreshold(self::officer([OfficerTrait::Indecisive]), $rules));
		static::assertSame(1, OfficerTraitEffects::getExposedTurns(self::officer([OfficerTrait::Cautious]), $rules));
		static::assertSame($rules->lossFactor * 0.75, OfficerTraitEffects::getLossFactor(self::officer([OfficerTrait::Tenacious]), $rules));
		static::assertSame(20.0, OfficerTraitEffects::getOutnumberedMoraleFloor(self::officer([OfficerTrait::Veteran])));
	}

	public function testRecklessAndCautiousDodge(): void
	{
		$cautious = self::officer([OfficerTrait::Cautious]);

		static::assertSame(1.1, OfficerTraitEffects::getAttackFactor(self::officer([OfficerTrait::Reckless])));
		static::assertSame(0.9, OfficerTraitEffects::getDodgeFactor(self::officer([OfficerTrait::Reckless])));
		static::assertSame(1.0, OfficerTraitEffects::getDodgeFactor($cautious));
		$cautious->status = FleetStatus::Withdrawn;
		static::assertSame(1.15, OfficerTraitEffects::getDodgeFactor($cautious));
	}

	public function testDefenderOnlyWhenItsSideDefends(): void
	{
		$state = new BattleState(grid: new ClusteredHexGrid(4), battlefieldSize: 5, seed: 1, rules: new BattleRules(engagement: new EngagementRules(defendingSide: 0)));

		static::assertSame(1.1, OfficerTraitEffects::getDefenseFactor($state, self::officer([OfficerTrait::Defender], side: 0)));
		static::assertSame(1.0, OfficerTraitEffects::getDefenseFactor($state, self::officer([OfficerTrait::Defender], side: 1)));
	}

	public function testCharismaticAndButcherRecovery(): void
	{
		$state = self::state();
		$fleet = self::deploy($state, self::officer([], 'fleet'), new Hex(1, 1));
		$butcher = self::deploy($state, self::officer([OfficerTrait::Butcher], 'butcher'), new Hex(3, 3));
		self::deploy($state, self::officer([OfficerTrait::Charismatic], 'charismatic'), new Hex(2, 1));
		self::deploy($state, self::officer([], 'ally'), new Hex(3, 2));
		$rules = new MoraleRules(alliedRecovery: 2.0);

		// Next to a charismatic ally: usual bonus + 2
		static::assertSame(4.0, OfficerTraitEffects::getAlliedRecovery($state, $fleet, $rules));
		// A butcher only gets half the usual bonus from its allies
		static::assertSame(1.0, OfficerTraitEffects::getAlliedRecovery($state, $butcher, $rules));
	}

	public function testCapitalHunterGoesForHeavyShips(): void
	{
		$state = self::state();
		$hunter = self::place($state, self::officer([OfficerTrait::CapitalHunter], 'hunter'), 'Cruiser', 1, new Hex(10, 10));
		self::place($state, self::officer([], 'fighters', side: 1), 'LightFighter', 50, new Hex(11, 10));
		$destroyer = self::place($state, self::officer([], 'destroyer', side: 1), 'Destroyer', 1, new Hex(10, 11));

		static::assertSame([ShipClass::Destroyer, ShipClass::Frigate], OfficerTraitEffects::getPreferredTargets($hunter));
		static::assertSame($destroyer, (new SelectTarget())($state, $hunter, true));

		// Interceptors and line ships keep their own targets
		$interceptor = self::place($state, self::officer([OfficerTrait::CapitalHunter], 'hunter2'), 'LightFighter', 50, new Hex(9, 11));
		static::assertSame($interceptor->type->preferredTargets, OfficerTraitEffects::getPreferredTargets($interceptor));
	}

	public function testAggressiveOfficerIgnoresPreferences(): void
	{
		$state = self::state();
		$bombers = self::place($state, self::officer([OfficerTrait::Aggressive], 'aggressive'), 'Bomber', 25, new Hex(10, 10));
		$closest = self::place($state, self::officer([], 'fighters', side: 1), 'LightFighter', 50, new Hex(11, 10));
		self::place($state, self::officer([], 'destroyer', side: 1), 'Destroyer', 1, new Hex(12, 10));

		// A bomber prefers destroyers, but an aggressive officer goes for the closest enemy
		static::assertSame($closest, (new SelectTarget())->getCandidates($state, $bombers, false)[0]);
	}

	public function testTacticianIsServedFirst(): void
	{
		$state = new BattleState(grid: new ClusteredHexGrid(4), battlefieldSize: 5, seed: 1, maxActionsPerTurn: 1);
		$slow = self::place($state, self::officer([OfficerTrait::Tactician], 'tactician'), 'Destroyer', 1, new Hex(10, 10));
		$fast = self::place($state, self::officer([], 'fast', side: 1), 'LightFighter', 50, new Hex(11, 10));

		// With a single action per turn, the tactician acts although the fighters have a much higher initiative
		$events = (new ResolveFire())($state, [$fast, $slow], new Randomizer(new Mt19937(1)));
		$attackers = array_map(fn (BattleEvent $event) => $event->payload['attacker'], array_filter($events, fn (BattleEvent $event) => BattleEvent::SQUADRON_FIRED === $event->type));

		static::assertSame([$slow->identifier], array_values(array_unique($attackers)));
	}

	/**
	 * @param list<OfficerTrait> $traits
	 */
	private static function officer(array $traits, string $identifier = 'fleet', int $side = 0): BattleFleet
	{
		return new BattleFleet(identifier: $identifier, name: $identifier, faction: $side + 1, side: $side, officerLevel: 1, formation: [], traits: $traits);
	}

	private static function state(): BattleState
	{
		return new BattleState(grid: new ClusteredHexGrid(4), battlefieldSize: 5, seed: 1);
	}

	/**
	 * Deploys a fleet with a single squadron at the center of a cluster.
	 */
	private static function deploy(BattleState $state, BattleFleet $fleet, Hex $cluster): BattleFleet
	{
		self::place($state, $fleet, 'Corvette', 1, $state->grid->clusterCenter($cluster));

		return $fleet;
	}

	private static function place(BattleState $state, BattleFleet $fleet, string $type, int $ships, Hex $cell): BattleSquadron
	{
		$fleet->cluster = $state->grid->clusterOf($cell);
		$squadron = new BattleSquadron(sprintf('%s-%d', $fleet->identifier, count($fleet->squadrons)), self::shipType($type), $ships, $cell, $fleet);
		$fleet->squadrons[] = $squadron;
		if (!in_array($fleet, $state->fleets, true)) {
			$state->addFleet($fleet);
		}
		$state->placeSquadron($squadron, $cell);

		return $squadron;
	}
}
