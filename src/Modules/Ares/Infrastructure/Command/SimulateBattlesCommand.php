<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Command;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Balance harness of the new battle system: runs many battles between two compositions
 * and reports win rates and durations.
 *
 * Example: app:ares:simulate-battles --a=LightFighter:50,Corvette:14 --b=Destroyer:3 --runs=200
 */
#[AsCommand(
	name: 'app:ares:simulate-battles',
	description: 'Run many battles between two fleet compositions and report win rates and durations',
)]
class SimulateBattlesCommand extends Command
{
	public function __construct(
		private readonly CreateBattle $createBattle,
		private readonly BattleEngine $battleEngine,
		private readonly ComputeBattleExperience $computeBattleExperience,
		#[Autowire('%ares.battle.turn_duration%')]
		private readonly int $turnDuration,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this
			->addOption('a', null, InputOption::VALUE_REQUIRED, 'Composition of side A, e.g. "LightFighter:50,Corvette:14"')
			->addOption('b', null, InputOption::VALUE_REQUIRED, 'Composition of side B')
			->addOption('b-arrival', null, InputOption::VALUE_REQUIRED, 'Turn at which side B arrives', '1')
			->addOption('a-faction', null, InputOption::VALUE_REQUIRED, 'Faction of side A, for its combat bonuses (0: no bonus)', '0')
			->addOption('b-faction', null, InputOption::VALUE_REQUIRED, 'Faction of side B, for its combat bonuses (0: no bonus)', '0')
			->addOption('a-level', null, InputOption::VALUE_REQUIRED, 'Officer level of side A', '1')
			->addOption('b-level', null, InputOption::VALUE_REQUIRED, 'Officer level of side B', '1')
			->addOption('runs', null, InputOption::VALUE_REQUIRED, 'Number of battles to run', '100')
			->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Seed of the first battle; the following ones increment it', '1');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);
		$compositionA = $this->parseComposition((string) $input->getOption('a'));
		$compositionB = $this->parseComposition((string) $input->getOption('b'));
		$runs = max(1, (int) $input->getOption('runs'));
		$seed = (int) $input->getOption('seed');
		$results = ['A' => 0, 'B' => 0, 'draw' => 0];
		$turns = [];
		$remainingPev = ['A' => [], 'B' => []];
		$experience = ['A' => [], 'B' => []];

		$style->progressStart($runs);

		for ($run = 0; $run < $runs; ++$run) {
			$state = ($this->createBattle)([
				['name' => 'A', 'side' => 0, 'faction' => (int) $input->getOption('a-faction'), 'officer_level' => (int) $input->getOption('a-level'), 'ships' => $compositionA],
				['name' => 'B', 'side' => 1, 'faction' => (int) $input->getOption('b-faction'), 'officer_level' => (int) $input->getOption('b-level'), 'ships' => $compositionB, 'arrival_turn' => (int) $input->getOption('b-arrival')],
			], $seed + $run);

			$this->battleEngine->resolveBattle($state);

			++$results[match ($state->winningSide) {
				0 => 'A',
				1 => 'B',
				default => 'draw',
			}];
			$turns[] = $state->turn;
			[$fleetA, $fleetB] = $state->fleets;
			$remainingPev['A'][] = $this->getRemainingPevRatio($fleetA);
			$remainingPev['B'][] = $this->getRemainingPevRatio($fleetB);
			$experiences = ($this->computeBattleExperience)($state);
			$experience['A'][] = $experiences[$fleetA->identifier] ?? 0;
			$experience['B'][] = $experiences[$fleetB->identifier] ?? 0;

			$style->progressAdvance();
		}

		$style->progressFinish();
		sort($turns);

		$style->table(['', 'Side A', 'Side B', 'Draw'], [
			['Wins', $this->percent($results['A'], $runs), $this->percent($results['B'], $runs), $this->percent($results['draw'], $runs)],
			['Remaining PEV (avg)', $this->percent(array_sum($remainingPev['A']), $runs), $this->percent(array_sum($remainingPev['B']), $runs), ''],
			['Officer XP (avg)', (int) round(array_sum($experience['A']) / $runs), (int) round(array_sum($experience['B']) / $runs), ''],
		]);
		$style->table(['Duration', 'Turns', 'Time'], [
			['Min', $turns[0], $this->formatDuration($turns[0])],
			['Median', $turns[intdiv(count($turns), 2)], $this->formatDuration($turns[intdiv(count($turns), 2)])],
			['Max', end($turns), $this->formatDuration(end($turns))],
		]);

		return Command::SUCCESS;
	}

	/**
	 * @return array<string, int>
	 */
	private function parseComposition(string $composition): array
	{
		$ships = [];

		foreach (array_filter(explode(',', $composition)) as $part) {
			[$type, $quantity] = array_map('trim', explode(':', $part)) + [1 => '0'];
			$ships[$type] = ($ships[$type] ?? 0) + (int) $quantity;
		}

		if ([] === $ships) {
			throw new \InvalidArgumentException('A composition is required for each side, e.g. "LightFighter:50,Corvette:14"');
		}

		return $ships;
	}

	private function getRemainingPevRatio(BattleFleet $fleet): float
	{
		$initialPev = $fleet->getFormationPev();

		return 0 === $initialPev ? 0.0 : $fleet->getPev() / $initialPev;
	}

	private function percent(float $value, int $total): string
	{
		return sprintf('%.1f %%', $value / $total * 100);
	}

	private function formatDuration(int $turns): string
	{
		$seconds = $turns * $this->turnDuration;

		return sprintf('%d min %02d s', intdiv($seconds, 60), $seconds % 60);
	}
}
