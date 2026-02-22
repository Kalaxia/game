<?php

declare(strict_types=1);

namespace App\Modules\Hephaistos\Cli;

use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
	name: 'app:hephaistos:schedule-actions',
	description: 'Retrieve planned actions and set them in Messenger'
)]
class ScheduleActions extends Command
{
	/**
	 * @param list<SchedulerInterface> $schedulers
	 */
	public function __construct(
		#[AutowireIterator('app.scheduler')]
		private readonly iterable $schedulers,
	) {
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		foreach ($this->schedulers as $scheduler) {
			$style->info(\sprintf('Scheduling %s actions', $scheduler::class));

			$scheduler->schedule();
		}

		$style->success('All schedulers have been processed !');

		return self::SUCCESS;
	}
}
