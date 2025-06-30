<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

/**
 * @extends ModelFactory<Color>
 */
class FactionFactory extends ModelFactory
{
	public function __construct(private readonly GetFactionsConfiguration $getFactionsConfiguration)
	{
		parent::__construct();
	}

	protected function getDefaults(): array
	{
		$identifier = self::faker()->unique()->numberBetween(1, 11);

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

	protected static function getClass(): string
	{
		return Color::class;
	}
}
