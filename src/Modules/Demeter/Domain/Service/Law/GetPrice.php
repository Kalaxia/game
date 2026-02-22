<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Law;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;

readonly class GetPrice
{
	public function __construct(
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(int $type, Color $faction, ?int $gameCycles): int
	{
		$baseLawPrice = LawResources::getInfo($type, 'price');

		if (LawResources::APPLICATION_MODE_INSTANTANEOUS === LawResources::getInfo($type, 'application_mode')) {
			return $baseLawPrice;
		}
		// Laws with duration are for now bonuses, their price is multiplied by the number of players it will apply to
		$activePlayers = $this->playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]);

		return $baseLawPrice * $gameCycles * $activePlayers;
	}
}
