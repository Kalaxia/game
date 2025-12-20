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
	name: 'app:meilisearch:truncate-indexes',
	description: 'Truncate Meilisearch indexes',
)]
final class TruncateMeilisearchIndexes extends Command
{

	public function __construct(
		private readonly SearchService $searchService,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$symfonyStyle = new SymfonyStyle($input, $output);

		$this->searchService->truncateAllIndexes();

		$symfonyStyle->success('All indexes have been truncated');

		return self::SUCCESS;
	}
}
