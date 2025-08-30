<?php

namespace App\Modules\Atlas\Infrastructure\Twig;

use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Specification\Player\CanPlayerAttackPlace;
use App\Modules\Ares\Domain\Specification\Player\CanPlayerMoveToPlace;
use App\Modules\Ares\Domain\Specification\Player\CanRecycle;
use App\Modules\Ares\Domain\Specification\Player\CanSpyPlace;
use App\Modules\Artemis\Application\Handler\AntiSpyHandler;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Domain\Service\Recycling\GetMissionTime;
use App\Modules\Athena\Domain\Specification\CanOrbitalBaseTradeWithPlace;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MapExtension extends AbstractExtension
{
	public function __construct(
		private readonly DurationHandler $durationHandler,
		private readonly GetTravelDuration $getTravelDuration,
		private readonly AntiSpyHandler $antiSpyHandler,
		private readonly GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private readonly GetMissionTime $getMissionTime,
		private readonly CurrentPlayerRegistry $currentPlayerRegistry,
		private readonly CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private readonly CommercialRouteRepositoryInterface $commercialRouteRepository,
	) {
	}

	#[\Override]
    public function getFilters(): array
	{
		return [
			new TwigFilter('coords', fn (System $system) => Game::formatCoord($system->xPosition, $system->yPosition)),
		];
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_base_antispy_radius', fn (OrbitalBase $base) => $this->antiSpyHandler->getAntiSpyRadius($base->antiSpyAverage)),
			new TwigFunction('get_travel_time', function (OrbitalBase $defaultBase, Place $place) {
				$departureDate = new \DateTimeImmutable();
				$arrivalDate = ($this->getTravelDuration)(
					origin: $defaultBase->place,
					destination: $place,
					departureDate: $departureDate,
					player: $this->currentPlayerRegistry->get(),
				);

				return $this->durationHandler->getDiff($departureDate, $arrivalDate);
			}),
			new TwigFunction('get_place_type', fn (string $type) => Game::convertPlaceType($type)),
			new TwigFunction('get_place_distance', fn (OrbitalBase $defaultBase, Place $place) => ($this->getDistanceBetweenPlaces)(
				$defaultBase->place,
				$place,
			)),
			new TwigFunction('get_max_travel_distance', fn () => Game::getMaxTravelDistance($this->currentPlayerBonusRegistry->getPlayerBonus())),
			new TwigFunction('get_place_demography', fn (Place $place) => Game::getSizeOfPlanet($place->population)),
			new TwigFunction('get_place_technosphere_improvement_coeff', fn (Place $place) => Game::getImprovementFromScientificCoef($place->coefHistory)),
			new TwigFunction('get_commercial_route_data', fn (OrbitalBase $defaultBase, Place $place) => $this->getCommercialRouteData($defaultBase, $place)),

			new TwigFunction('can_player_attack_place', function (Player $player, Place $place) {
				$specification = new CanPlayerAttackPlace($player);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_player_move_to_place', function (Player $player, Place $place, OrbitalBase $orbitalBase) {
				$specification = new CanPlayerMoveToPlace($player, $orbitalBase);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_orbital_base_trade_with_place', function (OrbitalBase $orbitalBase, Place $place) {
				$specification = new CanOrbitalBaseTradeWithPlace($orbitalBase);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_spy', function (Player $player, Place $place) {
				$specification = new CanSpyPlace($player);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_recycle', function (Player $player, Place $place) {
				$specification = new CanRecycle($player);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('get_recycling_mission_time', fn (OrbitalBase $orbitalBase, Place $place) => ($this->getMissionTime)($orbitalBase->place, $place, $this->currentPlayerRegistry->get())),
		];
	}

	private function getCommercialRouteData(OrbitalBase $defaultBase, Place $place): array
	{
		$routes = $this->commercialRouteRepository->getBaseRoutes($defaultBase);

		$data = [
			'proposed' => false,
			'not_accepted' => false,
			'stand_by' => false,
			'send_resources' => false,
			'slots' => \count($routes),
		];

		foreach ($routes as $route) {
			if ($route->destinationBase->id->equals($defaultBase->id) && CommercialRoute::PROPOSED == $route->statement) {
				--$data['slots'];
			}
			if (!$place->id->equals($route->originBase->place->id) && !$place->id->equals($route->destinationBase->place->id)) {
				continue;
			}
			$data = array_merge($data, match ($route->statement) {
				CommercialRoute::PROPOSED => ($defaultBase->id->equals($route->originBase->id))
						? ['proposed' => true]
						: ['not_accepted' => true],
				CommercialRoute::ACTIVE => ['send_resources' => true],
				CommercialRoute::STANDBY => ['stand_by' => true],
			});
		}

		return $data;
	}
}
