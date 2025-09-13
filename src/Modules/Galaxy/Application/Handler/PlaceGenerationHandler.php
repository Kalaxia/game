<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Classes\Library\Utils;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Galaxy\Application\Message\PlaceGenerationMessage;
use App\Modules\Galaxy\Domain\Entity\EmptyPlace;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Entity\UninhabitedPlace;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Domain\Enum\SystemType;
use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Shared\Application\Service\GetProportion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PlaceGenerationHandler
{
	public function __construct(
		private GetProportion $getProportion,
		private PlaceRepositoryInterface $placeRepository,
		private SystemRepositoryInterface $systemRepository,
		private LoggerInterface $galaxyGenerationLogger,
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

		$this->galaxyGenerationLogger->debug('Place generated successfully', [
			'type' => $type->name,
			'planet_type' => $place instanceof Planet ? $place->planetType->name : 'none',
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
		$pointsRep = random_int(1, 10);
		$abilities = [
			'population' => 0,
			'history' => 0,
			'resources' => 0,
		];

		// nombre de point a distribuer
		if ($pointsRep < 2) {
			$pointsTot = random_int(90, 100);
		} elseif ($pointsRep < 10) {
			$pointsTot = 100;
		} else {
			$pointsTot = random_int(100, 120);
		}

		// brassage du tableau
		Utils::shuffle($abilities);

		// répartition
		$z = 1;
		foreach ($abilities as $l => $v) {
			if ($z < 3) {
				$max = $pointsTot - ($z * 10);
				$max = $max < 10 ? 10 : $max;

				$points = random_int(10, $max);
				$abilities[$l] = $points;
				$pointsTot -= $points;
			} else {
				$abilities[$l] = $pointsTot < 5 ? 5 : $pointsTot;
			}

			++$z;
		}

		$population = $abilities['population'] * 250 / 100;
		$history = $abilities['history'];
		$resources = $abilities['resources'];

		// TODO DANGER
		$danger = match ($sectorDanger) {
			GalaxyConfiguration::DNG_CASUAL => random_int(0, Planet::DNG_CASUAL),
			GalaxyConfiguration::DNG_EASY => random_int(3, Planet::DNG_EASY),
			GalaxyConfiguration::DNG_MEDIUM => random_int(6, Planet::DNG_MEDIUM),
			GalaxyConfiguration::DNG_HARD => random_int(9, Planet::DNG_HARD),
			GalaxyConfiguration::DNG_VERY_HARD => random_int(12, Planet::DNG_VERY_HARD),
			default => 0,
		};

		$planetType = $this->determinePlanetType($system);

		return new Planet(
			id: Uuid::v4(),
			system: $system,
			position: $position,
			planetType: $planetType,
			player: null,
			name: null,
			population: $population,
			coefResources: $resources,
			coefHistory: $history,
			danger: $danger,
			maxDanger: $danger,
			typeOfBase: Planet::BASE_TYPE_COLONY,
			naturalResources: $this->determineNaturalResourcesCoefficients($planetType),
			updatedAt: new \DateTimeImmutable('-259200 seconds'),
		);
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
