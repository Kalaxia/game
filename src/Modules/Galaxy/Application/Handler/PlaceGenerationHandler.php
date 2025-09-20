<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Model\Color;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Entity\PlanetActivity;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ActivityCategory;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Repository\CompanyRepositoryInterface;
use App\Modules\Economy\Domain\Repository\PlanetActivityRepositoryInterface;
use App\Modules\Galaxy\Application\Message\PlaceGenerationMessage;
use App\Modules\Galaxy\Domain\Entity\EmptyPlace;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Domain\Enum\SystemType;
use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Shared\Application\Service\GetProportion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PlaceGenerationHandler
{
	public function __construct(
		private CompanyRepositoryInterface $companyRepository,
		private GetProportion $getProportion,
		private PlaceRepositoryInterface $placeRepository,
		private PlanetActivityRepositoryInterface $planetActivityRepository,
		private SystemRepositoryInterface $systemRepository,
		private LoggerInterface $galaxyGenerationLogger,
		private LockFactory $lockFactory,
	) {
	}

	public function __invoke(PlaceGenerationMessage $message): void
	{
		$system = $this->systemRepository->get($message->systemId)
			?? throw new \LogicException('System not found');

		$type = $this->getPlaceType($system->typeOfSystem);

		if (PlaceType::Planet === $type) {
			$place = $this->generatePlanet($system, $message->position, $message->sectorDanger);
		} elseif (PlaceType::Empty === $type) {
			$place = $this->generateEmptyPlace($system, $message->position);
		} else {
			$place = $this->generateRecyclingPlace($type, $system, $message->position);
		}

		$this->placeRepository->save($place);

		$this->determinePlanetActivities($place);

		$this->galaxyGenerationLogger->debug('Place generated successfully', [
			'type' => $type->name,
			'planet_type' => $place instanceof Planet ? $place->planetType->name : 'none',
			'population' => $place instanceof Planet ? $place->population : 'none',
			'position' => $message->position,
			'natural_resources' => $place instanceof Planet ? $place->naturalResources : 'none',
			'system_id' => $system->id->toRfc4122(),
			'sector_identifier' => $system->sector->identifier,
		]);
	}

	private function getPlaceType(SystemType $systemType): PlaceType
	{
		return PlaceType::from(($this->getProportion)(
			$systemType->getPlacesProportions(),
			random_int(1, 100),
		));
	}

	private function generatePlanet(System $system, int $position, int $sectorDanger): Planet
	{
		$planetType = $this->determinePlanetType($system);
		$danger = $this->determineDanger($sectorDanger);

		return new Planet(
			id: Uuid::v4(),
			system: $system,
			position: $position,
			planetType: $planetType,
			player: null,
			name: null,
			population: $this->determinePopulation($system, $planetType),
			danger: $danger,
			maxDanger: $danger,
			typeOfBase: Planet::BASE_TYPE_COLONY,
			naturalResources: $this->determineNaturalResourcesCoefficients($planetType),
			updatedAt: new \DateTimeImmutable('-259200 seconds'),
		);
	}

	private function determinePopulation(System $system, PlanetType $planetType): int
	{
		if ($system->sector->faction === null) {
			return 0;
		}

		if (random_int(0, 100) < 50) {
			return 0;
		}

		[$min, $max] = $planetType->getPopulationBaseRange();

		return random_int($min, $max);
	}

	private function determineDanger(int $sectorDanger): int
	{
		// TODO DANGER
		return match ($sectorDanger) {
			GalaxyConfiguration::DNG_CASUAL => random_int(0, Planet::DNG_CASUAL),
			GalaxyConfiguration::DNG_EASY => random_int(3, Planet::DNG_EASY),
			GalaxyConfiguration::DNG_MEDIUM => random_int(6, Planet::DNG_MEDIUM),
			GalaxyConfiguration::DNG_HARD => random_int(9, Planet::DNG_HARD),
			GalaxyConfiguration::DNG_VERY_HARD => random_int(12, Planet::DNG_VERY_HARD),
			default => 0,
		};
	}

	private function determinePlanetType(System $system): PlanetType
	{
		$planetTypes = $system->typeOfSystem->getPlanetTypeProportions();

		return PlanetType::{array_keys($planetTypes)[($this->getProportion)(
			$planetTypes,
			random_int(1, 100),
		) - 1]};
	}

	/**
	 * Get the basic natural resources coefficients for the given PlanetType and randomize the numbers.
	 *
	 * Removes the coefficients that result in a 0.
	 *
	 * @return array<value-of<ResourceType>, int>
	 */
	private function determineNaturalResourcesCoefficients(PlanetType $planetType): array
	{
		return array_filter(
			array_map(
				// We apply a random modifier to the natural resources coefficients but keep numbers between 0 and 100
				fn (int $coefficient) => max(0, min($coefficient + random_int(-20, 20), 100)),
				$planetType->getNaturalResourcesBaseCoefficients(),
			),
			// If the obtained coefficient equals 0, we remove it from the planet's natural resources
			fn (int $coefficient) => $coefficient > 0,
		);
	}



	/**
	 * @return list<PlanetActivity>
	 */
	private function determinePlanetActivities(Planet $place): void
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

	private function generateEmptyPlace(System $system, int $position): EmptyPlace
	{
		return new EmptyPlace(
			id: Uuid::v4(),
			system: $system,
			position: $position,
			updatedAt: new \DateTimeImmutable('-259200 seconds'),
		);
	}

	private function generateRecyclingPlace(PlaceType $type, System $system, int $position): Place
	{
		[
			'credits' => $credits,
			'resources' => $resources,
			'history' => $history,
		] = $type->getCoefficients();

		$resources = random_int(2000000, 20000000);

		return new ($type->getClassName())(
			id: Uuid::v4(),
			system: $system,
			position: $position,
			updatedAt: new \DateTimeImmutable('-259200 seconds'),
			resources: $resources,
			credits: $credits,
			history: $history,
		);
	}
}
