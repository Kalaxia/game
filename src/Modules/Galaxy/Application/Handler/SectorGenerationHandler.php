<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Economy\Application\Message\CompanyProductsGenerationMessage;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ActivityCategory;
use App\Modules\Economy\Domain\Repository\CompanyRepositoryInterface;
use App\Modules\Galaxy\Application\Message\SectorGenerationMessage;
use App\Modules\Galaxy\Application\Message\SystemGenerationMessage;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Domain\Service\CreateMultipleSystemCoordinates;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Shared\Application\Service\GetProportion;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SectorGenerationHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CompanyRepositoryInterface $companyRepository,
		private CreateMultipleSystemCoordinates $createMultipleSystemCoordinates,
		private EntityManagerInterface $entityManager,
		private GetProportion $getProportion,
		private SectorRepositoryInterface $sectorRepository,
		private MessageBusInterface $messageBus,
		private GalaxyConfiguration $galaxyConfiguration,
		private LoggerInterface $galaxyGenerationLogger,
		private SluggerInterface $slugger,
	) {
	}

	public function __invoke(SectorGenerationMessage $message): void
	{
		$faction = (null !== $message->factionIdentifier)
			? $this->colorRepository->getOneByIdentifier($message->factionIdentifier)
				?? throw new \LogicException('Faction not found')
			: null;

		$sector = new Sector(
			id: Uuid::v4(),
			identifier: $message->identifier,
			faction: $faction,
			xPosition: $message->xPosition,
			yPosition: $message->yPosition,
			xBarycentric: $message->xBarycenter,
			yBarycentric: $message->yBarycenter,
			tax: 5,
			name: $message->name,
			points: $message->points,
			population: 0,
			prime: null !== $faction,
		);

		$this->sectorRepository->save($sector);

		$this->galaxyGenerationLogger->info('Sector generated successfully', [
			'identifier' => $sector->identifier,
		]);

		if ($sector->faction !== null) {
			$this->generateSectorCompanies($sector);
		}

		$this->generateSystems($sector, $message->danger);
	}

	private function generateSectorCompanies(Sector $sector): void
	{
		foreach ($this->getSectorCompaniesProportions() as $activityName => $companiesCount) {
			$activity = Activity::from($activityName);

			for ($j = 0; $j < $companiesCount; $j++) {
				$name = sprintf(
					'Company %s %s-%s',
					$activityName,
					str_pad(strval($sector->identifier), 2, '0'),
					str_pad(strval($j + 1), 2, '0'),
				);

				$company = new Company(
					id: Uuid::v4(),
					name: $name,
					slug: $this->slugger->slug($name)->toString(),
					faction: $sector->faction,
					activity: $activity,
					credits: random_int(1, 3) * 1000000,
					createdAt: new \DateTimeImmutable(),
					updatedAt: new \DateTimeImmutable(),
				);

				$this->companyRepository->save($company, doFlush: false);

				$this->messageBus->dispatch(new CompanyProductsGenerationMessage($company->id));
			}
		}

		$this->entityManager->flush();
	}

	/**
	 * @return array<string, int>
	 */
	private function getSectorCompaniesProportions(): array
	{
		return array_column(array_map(
			function (Activity $activity): array {
				if ($activity->getCategory() === ActivityCategory::PrimaryProduction) {
					$companiesCount = 5;
				}

				if ($activity->getCategory() === ActivityCategory::Refinement) {
					$companiesCount = 3;
				}

				if ($activity->getCategory() === ActivityCategory::FinalProduction) {
					$companiesCount = 2;
				}

				return [$activity->value,  $companiesCount ?? 1];
			},
			Activity::cases(),
		), 1, 0);
	}

	/**
	 * Génère un ensemble de points aléatoires à l'intérieur du polygone du secteur,
	 * en appliquant un espacement minimal entre points.
	 *
	 * Algorithme:
	 * - Calcul de la boîte englobante du polygone
	 * - Rejection sampling: on tire un point aléatoire dans la bbox, on le garde s'il est dans le polygone
	 * - Filtre d'espacement: on impose une distance minimale entre points
	 *
	 * Paramétrage:
	 * - $baseCount: quantité nominale de points (ajustée par le danger)
	 * - $minSpacing: espacement minimal (peut varier selon l'échelle du polygone)
	 * - $maxAttempts: limite de tentatives pour éviter les boucles longues
	 *
	 * Note:
	 * - Pour un échantillonnage plus “blue-noise”, remplacez la boucle par l’algorithme de Bridson (Poisson-disc sampling).
	 * Cette version reste simple et efficace pour beaucoup de cas.
	 */
	private function generateSystems(Sector $sector, int $sectorDanger): void
	{
		// Paramètres simples: ajuster le nombre de points avec le "danger"
		$baseCount = 25; // valeur de base
		$targetCount = max(1, intval(round($baseCount * (1.0 + ($sectorDanger / 10.0)))));

		$points = ($this->createMultipleSystemCoordinates)(
			sector: $sector,
			targetCount: $targetCount,
			maxAttempts: $targetCount * 40,
		);

		foreach ($points as [$sx, $sy]) {
			$this->messageBus->dispatch(new SystemGenerationMessage(
				sectorIdentifier: $sector->identifier,
				sectorDanger: $sectorDanger,
				xPosition: intval(round($sx)),
				yPosition: intval(round($sy)),
				typeOfSystem: ($this->getProportion)(
					$this->galaxyConfiguration->galaxy['systemProportion'],
					random_int(1, 100),
				),
			));
		}
	}
}
