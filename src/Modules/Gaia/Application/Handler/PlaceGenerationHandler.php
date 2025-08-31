<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Classes\Library\Utils;
use App\Modules\Gaia\Application\Message\PlaceGenerationMessage;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Enum\PlaceType;
use App\Modules\Gaia\Domain\Enum\SystemType;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use App\Modules\Shared\Application\Service\GetProportion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PlaceGenerationHandler
{
	public function __construct(
		private GalaxyConfiguration $galaxyConfiguration,
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
			$stRES = 0;
		} elseif (PlaceType::Empty === $type) {
			$population = 0;
			$history = 0;
			$resources = 0;
			$stRES = 0;
		} else {
			[
				'credits' => $population,
				'resources' => $resources,
				'history' => $history,
			] = $type->getCoefficients();

			$stRES = random_int(2000000, 20000000);
		}

		// TODO DANGER
		$danger = match ($message->sectorDanger) {
			GalaxyConfiguration::DNG_CASUAL => random_int(0, Place::DNG_CASUAL),
			GalaxyConfiguration::DNG_EASY => random_int(3, Place::DNG_EASY),
			GalaxyConfiguration::DNG_MEDIUM => random_int(6, Place::DNG_MEDIUM),
			GalaxyConfiguration::DNG_HARD => random_int(9, Place::DNG_HARD),
			GalaxyConfiguration::DNG_VERY_HARD => random_int(12, Place::DNG_VERY_HARD),
			default => 0,
		};

		$place = new Place(
			id: Uuid::v4(),
			base: null,
			system: $system,
			typeOfPlace: $type,
			position: $message->position,
			population: $population,
			coefResources: $resources,
			coefHistory: $history,
			resources: $stRES,
			danger: $danger,
			maxDanger: $danger,
			updatedAt: new \DateTimeImmutable('-259200 seconds'),
		);

		$this->placeRepository->save($place);

		$this->galaxyGenerationLogger->debug('Place generated successfully', [
			'type' => $type->name,
			'position' => $message->position,
			'system_id' => $system->id->toRfc4122(),
			'sector_identifier' => $system->sector->identifier,
		]);
	}

	protected function getPlaceType(SystemType $systemType): PlaceType
	{
		return PlaceType::from(($this->getProportion)(
			$systemType->getPlacesProportions(),
			random_int(1, 100),
		));
	}
}
