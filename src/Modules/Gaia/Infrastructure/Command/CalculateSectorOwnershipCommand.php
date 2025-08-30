<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\Command;

use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Manager\SectorManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
	name: 'app:gaia:calculate-sector-ownership',
	description: 'Update sector ownership',
)]
class CalculateSectorOwnershipCommand extends Command
{
	public function __construct(
		private readonly SectorRepositoryInterface $sectorRepository,
		private readonly SectorManager $sectorManager,
	) {
		parent::__construct();
	}

	public function configure(): void
	{
		$this->addArgument('sector-id', InputArgument::OPTIONAL, 'The sector ID to process');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		if (null !== ($sectorId = $input->getArgument('sector-id'))) {
			if (!Uuid::isValid($sectorId)) {
				throw new \InvalidArgumentException('Invalid UUID given');
			}

			$sector = $this->sectorRepository->get(Uuid::fromString($sectorId))
				?? throw new \InvalidArgumentException('Sector not found');

			$this->processSector($style, $sector);
		} else {
			foreach ($this->sectorRepository->getAll() as $sector) {
				$this->processSector($style, $sector);
			}
		}

		return self::SUCCESS;
	}

	public function processSector(SymfonyStyle $style, Sector $sector): void
	{
		$style->info(sprintf('Processing sector %d', $sector->identifier));

		$this->sectorManager->calculateOwnership($sector);
	}
}
