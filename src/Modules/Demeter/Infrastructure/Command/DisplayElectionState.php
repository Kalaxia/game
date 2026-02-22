<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Command;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:demeter:display-election-state',
	description: 'Display the different durations and states of a specific faction'
)]
class DisplayElectionState extends Command
{
	public function __construct(
		private readonly ColorRepositoryInterface $factionRepository,
		private readonly PoliticalEventRepositoryInterface $electionRepository,
		private readonly GetFactionsConfiguration $getFactionsConfiguration,
		private readonly NextElectionDateCalculator $nextElectionDateCalculator,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument('faction-id', InputArgument::REQUIRED, 'The identifier of the desired faction');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$factionIdentifier = intval($input->getArgument('faction-id'));
		$faction = $this->factionRepository->getOneByIdentifier($factionIdentifier)
			?? throw new \InvalidArgumentException('Faction not found');

		$style = new SymfonyStyle($input, $output);

		$style->info(sprintf('Checking election state for %s', ($this->getFactionsConfiguration)($faction, 'popularName')));

		$lastElection = $this->electionRepository->getFactionLastPoliticalEvent($faction);

		$headers = [
			'Last election date',
			'Election statement',
			'Next campaign start',
			'Next campaign end',
			'Next Ballot',
		];

		$row = [
			$lastElection->endedAt->format('Y-m-d H:i:s'),
			$faction->mandateState,
			$this->nextElectionDateCalculator->getDateUntil(
				$faction,
				$faction->isDemocratic()
					? MandateState::DemocraticCampaign
					: MandateState::TheocraticCampaign
			)->format('Y-m-d H:i:s'),
			$this->nextElectionDateCalculator->getDateUntil(
				$faction,
				MandateState::DemocraticVote
			)->format('Y-m-d H:i:s'),
			$this->nextElectionDateCalculator->getDateUntil(
				$faction,
				MandateState::Active,
			)->format('Y-m-d H:i:s'),
		];

		if ($faction->isDemocratic()) {
			$headers[] = 'Next election';
			$row[] = $this->nextElectionDateCalculator->getDateUntil(
				$faction,
				MandateState::DemocraticVote,
			)->format('Y-m-d H:i:s');
		}

		$style->horizontalTable(
			headers: $headers,
			rows: [$row],
		);

		$style->title('Last elections');

		$style->table(
			[
				'Date',
			],
			array_map(
				fn (PoliticalEvent $election) => [$election->startedAt->format('Y-m-d H:i:s')],
				$this->electionRepository->getFactionPoliticalEvents($faction),
			),
		);

		return self::SUCCESS;
	}
}
