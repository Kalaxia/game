<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class CommanderSchoolExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);

		$container->setParameter('app.commander_school.credits_cost', $config['credits_cost']);
		$container->setParameter('app.commander_school.minimum_experience', $config['minimum_experience']);
		$container->setParameter('app.commander_school.maximum_experience', $config['maximum_experience']);
		$container->setParameter('app.commander_school.points', $config['points']);
	}

	public function getAlias(): string
	{
		return 'commander_school';
	}

	public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
	{
		return new CommanderSchoolConfiguration();
	}
}
