<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Modules\Galaxy\Application\Message\PlaceGenerationMessage;
use App\Modules\Galaxy\Domain\Entity\EmptyPlace;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Galaxy\Domain\Enum\SystemType;
use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePlanetActivities;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePlanetResourceCoefficients;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePlanetType;
use App\Modules\Galaxy\Domain\Service\Planet\DeterminePopulation;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Shared\Application\Service\GetProportion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PlaceGenerationHandler
{
	public function __construct(
		private DeterminePopulation                 $determinePopulation,
		private DeterminePlanetActivities			$determinePlanetActivities,
		private DeterminePlanetResourceCoefficients $determinePlanetResourceCoefficients,
		private DeterminePlanetType 				$determinePlanetType,
		private GetProportion                       $getProportion,
		private PlaceRepositoryInterface            $placeRepository,
		private SystemRepositoryInterface           $systemRepository,
		private LoggerInterface                     $galaxyGenerationLogger,
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

		($this->determinePlanetActivities)($place);

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
		$planetType = ($this->determinePlanetType)($system);
		$danger = $this->determineDanger($sectorDanger);

		return new Planet(
			id: Uuid::v4(),
			system: $system,
			position: $position,
			planetType: $planetType,
			player: null,
			name: null,
			population: ($this->determinePopulation)($system, $planetType),
			danger: $danger,
			maxDanger: $danger,
			typeOfBase: Planet::BASE_TYPE_COLONY,
			naturalResources: ($this->determinePlanetResourceCoefficients)($planetType),
			updatedAt: new \DateTimeImmutable('-259200 seconds'),
		);
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
