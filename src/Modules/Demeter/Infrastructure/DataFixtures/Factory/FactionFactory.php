<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Color>
 */
class FactionFactory extends PersistentProxyObjectFactory
{
	public function __construct(private readonly GetFactionsConfiguration $getFactionsConfiguration)
	{
		parent::__construct();
	}

	protected function defaults(): array
	{
		$identifier = self::faker()->unique()->randomElement([
			ColorResource::KALANKAR,
			ColorResource::VALKAR,
			ColorResource::FALKIRR,
			ColorResource::ADRANITE,
			ColorResource::MALGAR,
		]);

		return [
			'id' => Uuid::v4(),
			'identifier' => $identifier,
			'alive' => true,
			'isWinner' => false,
			'credits' => 0,
			'rankingPoints' => 0,
			'points' => 0,
			'electionStatement' => Color::MANDATE,
			'regime' => ($this->getFactionsConfiguration)($identifier, 'regime'),
			'isClosed' => false,
			'description' => null,
			'isInGame' => true,
			'relations' => [],
			// @TODO move that field to the future Server entity
			'victoryClaimedAt' => null,
			// @TODO get that field from the Election table
			'lastElectionHeldAt' => null,
		];
	}

	public static function class(): string
	{
		return Color::class;
	}
}
