<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Command;

use App\Modules\Shared\Infrastructure\Meilisearch\SearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:meilisearch:create-indexes',
	description: 'Create Meilisearch indexes',
)]
class CreateMeilisearchIndexes extends Command
{
	public function __construct(
		private readonly SearchService $searchService,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{

	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$symfonyStyle = new SymfonyStyle($input, $output);

		foreach ($this->searchService->index() as $index) {
			$symfonyStyle->info(sprintf('%s index has been created', $index->getUid()));
		}

		return self::SUCCESS;
	}
}
