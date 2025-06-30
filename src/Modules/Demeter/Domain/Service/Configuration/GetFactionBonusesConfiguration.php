<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Configuration;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GetFactionBonusesConfiguration
{

	public function __construct(
		#[Autowire('%app.faction_bonuses%')]
		private array $factionBonusesConfiguration,
	) {
	}

	public function __invoke(int $index): mixed
	{
		return $this->factionBonusesConfiguration[$index] ?? throw new \InvalidArgumentException(sprintf(
			'Faction bonus %d not found',
			$index,
		));
	}
}
