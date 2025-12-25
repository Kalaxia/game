<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\DependencyInjection;

use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ProductType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class IndustrySchemasExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$configuration = $this->getConfiguration($configs, $container);

		$config = $this->processConfiguration($configuration, $configs);
		$normalize = fn (string $section): array => array_reduce(
			$config[$section],
			function (array $carry, array $item) use ($section): array {
				$item['requirements'] = array_map(
					function (array $requirement) use ($item): array {
						if ($requirement['slug'] ?? null instanceof ComponentProductSlug) {
							$requirement['product_type'] = ProductType::Component;
						} elseif ($requirement['type'] ?? null instanceof ResourceType) {
							$requirement['product_type'] = ProductType::Resource;
						} else {
							throw new \LogicException(sprintf(
								'Requirement of item %s has an invalid requirement',
								$item['slug']->value,
							));
						}

						return $requirement;
					},
					$item['requirements'],
				);

				$carry[$item['slug']->value] = $item;

				return $carry;
			},
			[],
		);

		$config['ships'] = $normalize('ships');
		$config['components'] = $normalize('components');

		$container->setParameter('app.industry_schemas', $config);
	}

	public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
	{
		return new IndustrySchemasConfiguration();
	}
}
