<?php

namespace App\Modules\Athena\Helper;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;

readonly class ShipHelper
{
	public function __construct(
		private CurrentPlayerRegistry                  $currentPlayerRegistry,
		private TechnologyHelper                       $technologyHelper,
		private ShipQueueRepositoryInterface           $shipQueueRepository,
		private GetShipCategoriesConfiguration         $getShipCategoriesConfiguration,
	) {
	}

	/**
	 * TODO Refactor with Specification Pattern
	 */
	public function haveRights(int $shipId, string $type, $sup, int $quantity = 1): bool|string
	{
		if (null === ($shipCategory = ShipCategory::tryFrom($shipId))) {
			throw new \ErrorException(sprintf('shipId invalide %d (entre 0 et %d) dans haveRights de ShipResource', $shipId, count(ShipCategory::cases())));
		}

		switch ($type) {
			// assez de ressources pour construire ?
			case 'resource':
				$price = ($this->getShipCategoriesConfiguration)($shipCategory, 'resourcePrice') * $quantity;
				if (
					ColorResource::KALANKAR === $this->currentPlayerRegistry->get()->faction->identifier
					&& in_array($shipCategory, [ShipCategory::Cruiser, ShipCategory::HeavyCruiser])
				) {
					$price -= PercentageApplier::toInt($price, ColorResource::BONUS_EMPIRE_CRUISER);
				}

				return !($sup < $price);
			case 'queue':
				return true;
				// droit de construire le vaisseau ?
				// $sup est un objet de type Planet
			case 'shipTree':
				return true;
			// assez de pev dans le storage et dans la queue ?
			// $sup est un objet de type Planet
			case 'pev':
				return true;
			// a la technologie nécessaire pour constuire ce vaisseau ?
			// $sup est un objet de type Technology
			case 'techno':
				if (1 == $sup->getTechnology(($this->getShipCategoriesConfiguration)($shipId, 'techno'))) {
					return true;
				}
				return 'il vous faut développer la technologie ' . $this->technologyHelper->getInfo(($this->getShipCategoriesConfiguration)($shipId, 'techno'), 'name');
			default:
				throw new \ErrorException('type invalide dans haveRights de ShipResource');
		}
	}
}
