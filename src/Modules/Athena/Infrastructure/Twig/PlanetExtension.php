<?php

namespace App\Modules\Athena\Infrastructure\Twig;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Service\CalculateFleetCost;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Athena\Application\Handler\Tax\PopulationTaxHandler;
use App\Modules\Athena\Domain\Service\Base\Building\BuildingDataHandler;
use App\Modules\Athena\Domain\Service\Base\Building\GetTimeCost;
use App\Modules\Athena\Domain\Service\Base\GetCoolDownBeforeLeavingPlanet;
use App\Modules\Athena\Domain\Service\Base\GetMaxResourceStorage;
use App\Modules\Athena\Domain\Specification\CanLeavePlanet;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PlanetExtension extends AbstractExtension
{
	public function __construct(
		private readonly BuildingDataHandler            $buildingDataHandler,
		private readonly CalculateFleetCost             $calculateFleetCost,
		private readonly GetTimeCost                    $getTimeCost,
		private readonly GetCoolDownBeforeLeavingPlanet $getCoolDownBeforeLeavingPlanet,
		private readonly DurationHandler                $durationHandler,
		private readonly PlanetHelper                   $planetHelper,
		private readonly PopulationTaxHandler           $populationTaxHandler,
		private readonly GetMaxResourceStorage          $getMaxStorage,
		private readonly GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		#[Autowire('%game.ship_cost_reduction%')]
		private readonly float                          $shipCostReduction,
	) {
	}

	#[\Override]
    public function getFilters(): array
	{
		return [
			new TwigFilter('base_demography', fn (Planet $planet) => Game::getSizeOfPlanet($planet->population)),
			new TwigFilter('base_type', fn (Planet $planet) => PlaceResource::get($planet->typeOfBase, 'name')),
			new TwigFilter('scalar_base_type', fn (string $type) => PlaceResource::get($type, 'name')),
			new TwigFilter('base_storage_percent', fn (Planet $planet) => Format::numberFormat($planet->resourcesStorage / ($this->getMaxStorage)($planet) * 100)),
			new TwigFilter('base_coords', fn (Planet $planet) => Game::formatCoord(
				$planet->system->xPosition,
				$planet->system->yPosition,
				$planet->position,
				$planet->system->sector->identifier,
			)),
			// @TODO Factorize that coords call
			new TwigFilter('spy_report_coords', fn (SpyReport $spyReport) => Game::formatCoord($spyReport->place->system->xPosition, $spyReport->place->system->yPosition, $spyReport->place->position, $spyReport->place->system->sector->identifier)),
		];
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_planet_size', fn (int|float $population) => Game::getSizeOfPlanet($population)),
			new TwigFunction('get_planet_type_info', fn (string $baseType, string $info) => PlaceResource::get($baseType, $info)),
			// TODO Move to specification
			new TwigFunction('can_leave_planet', fn (Planet $planet) => $this->durationHandler->getHoursDiff(new \DateTimeImmutable(), $planet->createdAt) < ($this->getCoolDownBeforeLeavingPlanet)()),
			new TwigFunction('get_time_until_cooldown_end', fn (Planet $planet) => ($this->getCoolDownBeforeLeavingPlanet)() - $this->durationHandler->getHoursDiff(new \DateTimeImmutable(), $planet->createdAt)),
			new TwigFunction('get_cooldown_before_leaving_base', fn () => ($this->getCoolDownBeforeLeavingPlanet)()),
			new TwigFunction('get_planet_production', fn (Planet $planet, ?int $level = null) => Game::resourceProduction(
				$this->planetHelper->getBuildingInfo(
					PlanetResource::REFINERY,
					'level',
					$level ?? $planet->levelRefinery,
					'refiningCoefficient'
				),
				$planet->coefResources,
			)),
			new TwigFunction('get_building_info', fn (int $buildingNumber, string $info, int $level = 0, string $sub = 'default') => $this->planetHelper->getInfo($buildingNumber, $info, $level, $sub)),
			new TwigFunction('get_building_resource_cost', fn (int $buildingNumber, int $level) => $this->buildingDataHandler->getBuildingResourceCost($buildingNumber, $level)),
			new TwigFunction('get_building_time_cost', fn (int $buildingNumber, int $level) => ($this->getTimeCost)($buildingNumber, $level)),
			new TwigFunction('get_building_level_range', fn (int $currentLevel) => \range(
				($currentLevel < 3) ? 1 : $currentLevel - 2,
				(($currentLevel > 35) ? 41 : $currentLevel + 5) - 1,
			)),
			new TwigFunction('get_planet_fleet_cost', fn (Planet $planet) => ($this->calculateFleetCost)($planet->shipStorage, false)),
			// TODO check if bonus must be applied here (previously Game::getTaxFromPopulation without bonus applied)
			new TwigFunction('get_planet_tax', fn (Planet $planet, int $taxCoeff) => $this->populationTaxHandler->getPopulationTax($planet)),
			// @TODO Improve that part
			new TwigFunction('get_planet_image', fn (Planet $planet) => sprintf(
				'1-%s',
				Game::getSizeOfPlanet($planet->population),
			)),
			// @TODO move to a rightful place
			new TwigFunction('get_ship_transaction_cost', fn (Transaction $transaction) => ($this->getShipCategoriesConfiguration)($transaction->identifier, 'cost') * $this->shipCostReduction * $transaction->quantity),
			new TwigFunction('can_leave_planet', function (Planet $planet) {
				$canLeaveBase = new CanLeavePlanet(($this->getCoolDownBeforeLeavingPlanet)());

				return $canLeaveBase->isSatisfiedBy($planet);
			}),
		];
	}
}
