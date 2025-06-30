<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Configuration;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GetFactionsConfiguration
{
	public function __construct(
		#[Autowire('%app.factions%')]
		private array $factionsConfiguration,
	) {
	}

	public function __invoke(Color|int $faction, string $info): mixed
	{
		$identifier = ($faction instanceof Color) ? $faction->identifier : $faction;

		return $this->factionsConfiguration[$identifier][$info] ?? throw new \InvalidArgumentException(sprintf(
			'Faction configuration %s not found for Faction %d',
			$info,
			$identifier,
		));
	}
}
