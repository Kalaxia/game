<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Color>
 */
class FactionFactory extends PersistentObjectFactory
{
	public function __construct(
		private readonly GetFactionsConfiguration $getFactionsConfiguration,
		#[Autowire('%game.available_factions%')]
		private readonly array $availableFactions,
	) {
		parent::__construct();
	}

	protected function defaults(): array
	{
		$identifier = self::faker()->unique()->randomElement($this->availableFactions);

		return [
			'id' => Uuid::v4(),
			'identifier' => $identifier,
			'alive' => true,
			'isWinner' => false,
			'credits' => 0,
			'rankingPoints' => 0,
			'points' => 0,
			'mandateState' => MandateState::Active,
			'regime' => ($this->getFactionsConfiguration)($identifier, 'regime'),
			'isClosed' => false,
			'description' => null,
			'isInGame' => true,
			'relations' => [],
			// @TODO move that field to the future Server entity
			'victoryClaimedAt' => null,
		];
	}

	public static function class(): string
	{
		return Color::class;
	}
}
