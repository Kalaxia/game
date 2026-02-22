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
use App\Modules\Athena\Domain\Specification\CanPlanetTradeWithPlace;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Galaxy\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\System;
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
			new TwigFunction('get_planet_antispy_radius', fn (Planet $base) => $this->antiSpyHandler->getAntiSpyRadius($base->antiSpyAverage)),
			new TwigFunction('get_travel_time', function (Place $defaultBase, Place $place) {
				$departureDate = new \DateTimeImmutable();
				$arrivalDate = ($this->getTravelDuration)(
					origin: $defaultBase,
					destination: $place,
					departureDate: $departureDate,
					player: $this->currentPlayerRegistry->get(),
				);

				return $this->durationHandler->getDiff($departureDate, $arrivalDate);
			}),
			new TwigFunction('get_place_distance', fn (Place $defaultBase, Place $place) => ($this->getDistanceBetweenPlaces)(
				$defaultBase,
				$place,
			)),
			new TwigFunction('get_max_travel_distance', fn () => Game::getMaxTravelDistance($this->currentPlayerBonusRegistry->getPlayerBonus())),
			new TwigFunction('get_place_demography', fn (Planet $place) => Game::getSizeOfPlanet($place->population)),
			new TwigFunction('get_place_technosphere_improvement_coeff', fn (Planet $place) => Game::getImprovementFromScientificCoef($place->coefHistory)),
			new TwigFunction('get_commercial_route_data', fn (Planet $defaultBase, Planet $place) => $this->getCommercialRouteData($defaultBase, $place)),

			new TwigFunction('can_player_attack_place', function (Player $player, Planet $place) {
				$specification = new CanPlayerAttackPlace($player);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_player_move_to_place', function (Player $player, Planet $place, Planet $planet) {
				$specification = new CanPlayerMoveToPlace($player, $planet);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_planet_trade_with_place', function (Planet $planet, Planet $place) {
				$specification = new CanPlanetTradeWithPlace($planet);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_spy', function (Player $player, Planet $place) {
				$specification = new CanSpyPlace($player);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('can_recycle', function (Player $player, Place $place) {
				$specification = new CanRecycle($player);

				return $specification->isSatisfiedBy($place);
			}),
			new TwigFunction('get_recycling_mission_time', fn (Planet $planet, Place $place) => ($this->getMissionTime)($planet, $place, $this->currentPlayerRegistry->get())),
		];
	}

	private function getCommercialRouteData(Planet $defaultBase, Planet $place): array
	{
		$routes = $this->commercialRouteRepository->getPlanetRoutes($defaultBase);

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
			if (!$place->id->equals($route->originBase->id) && !$place->id->equals($route->destinationBase->id)) {
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
