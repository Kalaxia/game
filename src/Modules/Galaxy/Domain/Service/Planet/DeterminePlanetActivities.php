<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Service\Planet;

use App\Modules\Demeter\Model\Color;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Entity\PlanetActivity;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ActivityCategory;
use App\Modules\Economy\Domain\Repository\CompanyRepositoryInterface;
use App\Modules\Economy\Domain\Repository\PlanetActivityRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Uid\Uuid;

readonly class DeterminePlanetActivities
{
	public function __construct(
		private CompanyRepositoryInterface $companyRepository,
		private LockFactory $lockFactory,
		private LoggerInterface $galaxyGenerationLogger,
		private PlanetActivityRepositoryInterface $planetActivityRepository,
	) {
	}

	public function __invoke(Place $place): void
	{
		if (!$place instanceof Planet) {
			return;
		}

		if (0 === $place->population) {
			return;
		}

		$scores = [];
		$calculateScore = function (string $dependencyLevel, array $scores, array $dependencies, Activity $activity, int $categoryModifier) use ($place): array {
			if (!isset($dependencies[$dependencyLevel])) {
				return $scores;
			}

			foreach ($dependencies[$dependencyLevel] as $dependency) {
				$scores[$activity->value] = ($scores[$activity->value] ?? 0)
					+ ($place->naturalResources[$dependency->value] ?? 0)
					+ $categoryModifier;
			}

			return $scores;
		};

		foreach (Activity::cases() as $activity) {
			$dependencies = $activity->getDependencies();
			$categoryModifier = match ($activity->getCategory()) {
				ActivityCategory::PrimaryProduction => random_int(0, 20),
				ActivityCategory::Refinement => random_int(-10, 10),
				ActivityCategory::FinalProduction => random_int(-20, 5),
				default => 0,
			};

			$scores = $calculateScore('local', $scores, $dependencies, $activity, $categoryModifier);
			$scores = $calculateScore('any', $scores, $dependencies, $activity, $categoryModifier);
		}

		arsort($scores);

		$this->galaxyGenerationLogger->debug('Planet activities', [
			'planet_type' => $place->planetType->name,
			'population' => $place->population,
			'natural_resources' => $place->naturalResources,
			'sector_identifier' => $place->system->sector->identifier,
			'activities' => array_slice($scores, 0, 3),
		]);

		$limit = 3;
		$activitiesCount = 0;

		foreach ($scores as $activityValue => $score) {
			$activity = Activity::from($activityValue);
			if (null === ($company = $this->findCompany($activity, $place->system->sector->faction))) {
				if (null === ($company = $this->findCompany($activity, null))) {
					continue;
				}
			}

			$activitiesCount++;

			$planetActivity = new PlanetActivity(
				id: Uuid::v4(),
				planet: $place,
				activity: $activity,
				company: $company,
				createdAt: $place->createdAt,
				updatedAt: $place->createdAt,
			);

			$this->galaxyGenerationLogger->debug('Company {companyName} is assigned to activity {activityName}', [
				'companyName' => $company->name,
				'activityName' => $activity->name,
				'sector_identifier' => $place->system->sector->identifier,
			]);

			$this->planetActivityRepository->save($planetActivity, doFlush: false);

			if ($activitiesCount >= $limit) {
				break;
			}
		}
	}

	private function findCompany(Activity $activity, ?Color $faction): ?Company
	{
		$candidates = $this->companyRepository->searchCandidateCompaniesForNewActivity(
			activity: $activity,
			faction: $faction,
		);

		foreach ($candidates as $candidate) {
			$lock = $this->lockFactory->createLock(sprintf('company_%s', $candidate->id->toRfc4122()));
			if (!$lock->acquire()) {
				continue;
			}

			$candidate->credits -= $activity->getCost();

			$this->companyRepository->save($candidate);

			$lock->release();

			return $candidate;
		}

		return null;
	}
}
