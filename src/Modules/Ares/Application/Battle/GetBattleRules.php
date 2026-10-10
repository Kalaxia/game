<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\Rules\BattleRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\CommandRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\EngagementRules;
use App\Modules\Ares\Domain\Model\Battle\Rules\MoraleRules;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds the optional battle rules (D10 to D12) from the Ares configuration.
 */
readonly class GetBattleRules
{
	/**
	 * @param array{base_pev: int, pev_per_level: int, malus_per_excess: float, max_malus: float}                                                                                                                                              $command
	 * @param array{delay: int, min_force_ratio: float}                                                                                                                                                                                        $engagement
	 * @param array{loss_factor: float, victory_factor: float, outnumbered_penalty: float, recovery: float, allied_recovery: float, withdrawal_threshold: float, flight_threshold: float, remobilisation_threshold: float, exposed_turns: int} $morale
	 */
	public function __construct(
		#[Autowire('%ares.battle.command%')]
		private array $command,
		#[Autowire('%ares.battle.engagement%')]
		private array $engagement,
		#[Autowire('%ares.battle.morale%')]
		private array $morale,
		#[Autowire('%ares.battle.turn_duration%')]
		private int $turnDuration,
	) {
	}

	/**
	 * @param int $defendingSide side already in orbit when the attackers arrive
	 */
	public function __invoke(int $defendingSide = 0): BattleRules
	{
		return new BattleRules(
			command: new CommandRules(
				basePev: $this->command['base_pev'],
				pevPerLevel: $this->command['pev_per_level'],
				malusPerExcess: $this->command['malus_per_excess'],
				maxMalus: $this->command['max_malus'],
			),
			engagement: new EngagementRules(
				defendingSide: $defendingSide,
				delayTurns: (int) ceil($this->engagement['delay'] / max(1, $this->turnDuration)),
				minForceRatio: $this->engagement['min_force_ratio'],
			),
			morale: new MoraleRules(
				lossFactor: $this->morale['loss_factor'],
				victoryFactor: $this->morale['victory_factor'],
				outnumberedPenalty: $this->morale['outnumbered_penalty'],
				recovery: $this->morale['recovery'],
				alliedRecovery: $this->morale['allied_recovery'],
				withdrawalThreshold: $this->morale['withdrawal_threshold'],
				flightThreshold: $this->morale['flight_threshold'],
				remobilisationThreshold: $this->morale['remobilisation_threshold'],
				exposedTurns: $this->morale['exposed_turns'],
			),
		);
	}
}
