<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Entity\ComponentProduct;
use App\Modules\Economy\Domain\Entity\ResourceProduct;
use App\Modules\Economy\Domain\Entity\ShipProduct;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

readonly class GenerateCompanyProducts
{
	public function __construct(
		private EntityManagerInterface $entityManager,
		private ProductRepositoryInterface $productRepository,
	) {
	}

	public function __invoke(Company $company): void
	{
		$generateProducts = match ($company->activity) {
			Activity::Breeding => $this->getResourceProductGenerator([ResourceType::Meat]),
			Activity::Brewery => $this->getResourceProductGenerator([ResourceType::Beer, ResourceType::Digestive]),
			Activity::CarbonExtractor => $this->getResourceProductGenerator([ResourceType::Carbon]),
			Activity::CobaltMine => $this->getResourceProductGenerator([ResourceType::Cobalt]),
			Activity::CoffeeFarm => $this->getResourceProductGenerator([ResourceType::Coffee]),
			Activity::DeuteriumExtractor => $this->getResourceProductGenerator([ResourceType::Deuterium]),
			Activity::Farm => $this->getResourceProductGenerator([ResourceType::Cereals]),
			Activity::FishingPort => $this->getResourceProductGenerator([ResourceType::Fish]),
			Activity::HydrogenExtractor => $this->getResourceProductGenerator([ResourceType::Hydrogen]),
			Activity::HeliumExtractor => $this->getResourceProductGenerator([ResourceType::Helium]),
			Activity::NitrogenExtractor => $this->getResourceProductGenerator([ResourceType::Nitrogen]),
			Activity::Orchard => $this->getResourceProductGenerator([ResourceType::Fruits]),
			Activity::PlatinumMine => $this->getResourceProductGenerator([ResourceType::Platinum]),
			Activity::TeaFarm => $this->getResourceProductGenerator([ResourceType::Tea]),
			Activity::SaltFarm => $this->getResourceProductGenerator([ResourceType::Salt]),
			Activity::SugarFarm => $this->getResourceProductGenerator([ResourceType::Sugar]),
			Activity::Vineyard => $this->getResourceProductGenerator([ResourceType::Wine]),
			Activity::TitaniumMine => $this->getResourceProductGenerator([ResourceType::Titanium]),
			Activity::CrystalMine => $this->getResourceProductGenerator([ResourceType::Crystal]),
			Activity::UraniumMine => $this->getResourceProductGenerator([ResourceType::Uranium]),
			Activity::TritiumExtractor => $this->getResourceProductGenerator([ResourceType::Tritium]),
			Activity::WeaponFactory => $this->getComponentProductGenerator(ComponentType::Weapon),
			Activity::PropulsorFactory => $this->getComponentProductGenerator(ComponentType::Propulsor),
			Activity::ShieldFactory => $this->getComponentProductGenerator(ComponentType::ShieldGenerator),
			Activity::Shipyard => $this->getShipProductGenerator(),
			Activity::RheniumExtractor => $this->getResourceProductGenerator([ResourceType::Rhenium]),
			default => throw new \LogicException(sprintf('Activity %s is not linked to a product generator', $company->activity->value)),
		};

		foreach ($generateProducts($company) as $product) {
			$this->productRepository->save($product, doFlush: false);
		}
		$this->entityManager->flush();
	}

	private function getResourceProductGenerator(array $resourceTypes): callable
	{
		return static function (Company $company) use ($resourceTypes): \Generator {
			foreach ($resourceTypes as $resourceType) {
				yield new ResourceProduct(
					id: Uuid::v4(),
					company: $company,
					slug: $resourceType,
					createdAt: new \DateTimeImmutable(),
				);
			}
		};
	}

	private function getComponentProductGenerator(ComponentType $componentType): callable
	{
		return static function (Company $company) use ($componentType): \Generator {
			$generateComponentProduct = static fn (ComponentProductSlug $componentProductSlug) => new ComponentProduct(
				id: Uuid::v4(),
				company: $company,
				type: $componentType,
				slug: $componentProductSlug,
				createdAt: new \DateTimeImmutable(),
			);

			if (ComponentType::Weapon === $componentType) {
				yield $generateComponentProduct(ComponentProductSlug::SmallLaserCannon);
				yield $generateComponentProduct(ComponentProductSlug::LaserCannon);
				yield $generateComponentProduct(ComponentProductSlug::HeavyLaserCannon);
				yield $generateComponentProduct(ComponentProductSlug::LaserTurret);
				yield $generateComponentProduct(ComponentProductSlug::LaserBattery);
				yield $generateComponentProduct(ComponentProductSlug::MissileLauncher);
				yield $generateComponentProduct(ComponentProductSlug::TorpedoLauncher);
			}

			if (ComponentType::Propulsor === $componentType) {
				yield $generateComponentProduct(ComponentProductSlug::LightHydrogenPropulsor);
				yield $generateComponentProduct(ComponentProductSlug::TritiumPropulsor);
				yield $generateComponentProduct(ComponentProductSlug::UraniumPropulsor);
				yield $generateComponentProduct(ComponentProductSlug::HeavyUraniumPropulsor);
			}

			if (ComponentType::ShieldGenerator === $componentType) {
				yield $generateComponentProduct(ComponentProductSlug::NeutronShieldGenerator);
				yield $generateComponentProduct(ComponentProductSlug::PlasmaShieldGenerator);
				yield $generateComponentProduct(ComponentProductSlug::HeavyPlasmaShieldGenerator);
			}
		};
	}

	private function getShipProductGenerator(): callable
	{
		return static function (Company $company): \Generator {
			$createdAt = new \DateTimeImmutable();

			$generateShipProduct = static fn (ShipCategory $shipCategory, ShipProductSlug $shipProductSlug) => new ShipProduct(
				id: Uuid::v4(),
				company: $company,
				shipCategory: $shipCategory,
				slug: $shipProductSlug,
				createdAt: $createdAt,
			);

			yield $generateShipProduct(ShipCategory::Fighter, ShipProductSlug::Fighter);
			yield $generateShipProduct(ShipCategory::HeavyFighter, ShipProductSlug::HeavyFighter);
			yield $generateShipProduct(ShipCategory::LightCorvette, ShipProductSlug::LightCorvette);
			yield $generateShipProduct(ShipCategory::Corvette, ShipProductSlug::Corvette);
			yield $generateShipProduct(ShipCategory::HeavyCorvette, ShipProductSlug::HeavyCorvette);
			yield $generateShipProduct(ShipCategory::LightFrigate, ShipProductSlug::LightFrigate);
			yield $generateShipProduct(ShipCategory::Frigate, ShipProductSlug::Frigate);
			yield $generateShipProduct(ShipCategory::Destroyer, ShipProductSlug::Destroyer);
			yield $generateShipProduct(ShipCategory::HeavyDestroyer, ShipProductSlug::HeavyDestroyer);
			yield $generateShipProduct(ShipCategory::Cruiser, ShipProductSlug::Cruiser);
			yield $generateShipProduct(ShipCategory::HeavyCruiser, ShipProductSlug::HeavyCruiser);
		};
	}
}
