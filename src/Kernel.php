<?php

namespace App;

use App\Modules\Ares\Infrastructure\DependencyInjection\ShipCategoryExtension;
use App\Modules\Demeter\Infrastructure\DependencyInjection\FactionBonusExtension;
use App\Modules\Demeter\Infrastructure\DependencyInjection\FactionExtension;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
	use MicroKernelTrait;

	protected function build(ContainerBuilder $container): void
	{
		parent::build($container);

		$factionExtension = new FactionExtension();
		$container->registerExtension($factionExtension);
		$container->loadFromExtension($factionExtension->getAlias());

		$factionBonusExtension = new FactionBonusExtension();
		$container->registerExtension($factionBonusExtension);
		$container->loadFromExtension($factionBonusExtension->getAlias());

		$shipCategoriesExtension = new ShipCategoryExtension();
		$container->registerExtension($shipCategoriesExtension);
		$container->loadFromExtension($shipCategoriesExtension->getAlias());
	}
}
