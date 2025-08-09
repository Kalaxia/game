<?php

namespace App\Modules\Athena\Infrastructure\Twig;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Ares\Domain\Service\CalculateFleetCost;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Athena\Application\Handler\Tax\PopulationTaxHandler;
use App\Modules\Athena\Domain\Service\Base\Building\BuildingDataHandler;
use App\Modules\Athena\Domain\Service\Base\Building\GetTimeCost;
use App\Modules\Athena\Domain\Service\Base\GetCoolDownBeforeLeavingBase;
use App\Modules\Athena\Domain\Service\Base\GetMaxResourceStorage;
use App\Modules\Athena\Domain\Specification\CanLeaveOrbitalBase;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Shared\Application\Handler\DurationHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class OrbitalBaseExtension extends AbstractExtension
{
	public function __construct(
		private readonly BuildingDataHandler          $buildingDataHandler,
		private readonly CalculateFleetCost           $calculateFleetCost,
		private readonly GetTimeCost                  $getTimeCost,
		private readonly GetCoolDownBeforeLeavingBase $getCoolDownBeforeLeavingBase,
		private readonly DurationHandler              $durationHandler,
		private readonly OrbitalBaseHelper            $orbitalBaseHelper,
		private readonly PopulationTaxHandler         $populationTaxHandler,
		private readonly GetMaxResourceStorage        $getMaxStorage,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	#[\Override]
    public function getFilters(): array
	{
		return [
			new TwigFilter('base_demography', fn (OrbitalBase $orbitalBase) => Game::getSizeOfPlanet($orbitalBase->place->population)),
			new TwigFilter('base_type', fn (OrbitalBase $orbitalBase) => PlaceResource::get($orbitalBase->typeOfBase, 'name')),
			new TwigFilter('scalar_base_type', fn (string $type) => PlaceResource::get($type, 'name')),
			new TwigFilter('base_storage_percent', fn (OrbitalBase $orbitalBase) => Format::numberFormat($orbitalBase->resourcesStorage / ($this->getMaxStorage)($orbitalBase) * 100)),
			new TwigFilter('base_coords', fn (OrbitalBase $orbitalBase) => Game::formatCoord(
				$orbitalBase->place->system->xPosition,
				$orbitalBase->place->system->yPosition,
				$orbitalBase->place->position,
				$orbitalBase->place->system->sector->identifier,
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
			new TwigFunction('get_base_type_info', fn (string $baseType, string $info) => PlaceResource::get($baseType, $info)),
			// TODO Move to specification
			new TwigFunction('can_leave_base', fn (OrbitalBase $orbitalBase) => $this->durationHandler->getHoursDiff(new \DateTimeImmutable(), $orbitalBase->createdAt) < ($this->getCoolDownBeforeLeavingBase)()),
			new TwigFunction('get_time_until_cooldown_end', fn (OrbitalBase $orbitalBase) => ($this->getCoolDownBeforeLeavingBase)() - $this->durationHandler->getHoursDiff(new \DateTimeImmutable(), $orbitalBase->createdAt)),
			new TwigFunction('get_cooldown_before_leaving_base', fn () => ($this->getCoolDownBeforeLeavingBase)()),
			new TwigFunction('get_base_production', fn (OrbitalBase $orbitalBase, ?int $level = null) => Game::resourceProduction(
				$this->orbitalBaseHelper->getBuildingInfo(
					OrbitalBaseResource::REFINERY,
					'level',
					$level ?? $orbitalBase->levelRefinery,
					'refiningCoefficient'
				),
				$orbitalBase->place->coefResources,
			)),
			new TwigFunction('get_building_info', fn (int $buildingNumber, string $info, int $level = 0, string $sub = 'default') => $this->orbitalBaseHelper->getInfo($buildingNumber, $info, $level, $sub)),
			new TwigFunction('get_building_resource_cost', fn (int $buildingNumber, int $level) => $this->buildingDataHandler->getBuildingResourceCost($buildingNumber, $level)),
			new TwigFunction('get_building_time_cost', fn (int $buildingNumber, int $level) => ($this->getTimeCost)($buildingNumber, $level)),
			new TwigFunction('get_building_level_range', fn (int $currentLevel) => \range(
				($currentLevel < 3) ? 1 : $currentLevel - 2,
				(($currentLevel > 35) ? 41 : $currentLevel + 5) - 1,
			)),
			new TwigFunction('get_base_fleet_cost', fn (OrbitalBase $base) => ($this->calculateFleetCost)($base->shipStorage, false)),
			// TODO check if bonus must be applied here (previously Game::getTaxFromPopulation without bonus applied)
			new TwigFunction('get_base_tax', fn (OrbitalBase $base, int $taxCoeff) => $this->populationTaxHandler->getPopulationTax($base)),
			// @TODO Improve that part
			new TwigFunction('get_base_image', fn (OrbitalBase $base) => sprintf(
				'1-%s',
				Game::getSizeOfPlanet($base->place->population),
			)),
			// @TODO move to a rightful place
			new TwigFunction('get_ship_transaction_cost', fn (Transaction $transaction) => ($this->getShipCategoriesConfiguration)($transaction->identifier, 'cost') * ShipResource::COST_REDUCTION * $transaction->quantity),
			new TwigFunction('can_leave_orbital_base', function (OrbitalBase $orbitalBase) {
				$canLeaveBase = new CanLeaveOrbitalBase(($this->getCoolDownBeforeLeavingBase)());

				return $canLeaveBase->isSatisfiedBy($orbitalBase);
			}),
		];
	}
}
