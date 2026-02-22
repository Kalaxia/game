<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Model;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class PlayerRanking
{
	// set number of player before you (remove 1) in rank view
	public const PREV = 4;
	// set number of player after you in rank view
	public const NEXT = 8;
	// PREV + NEXT
	public const STEP = 12;
	// set number of player on ajax load page
	public const PAGE = 10;

	public function __construct(
		public Uuid $id,
		public Ranking $ranking,
		public Player $player,
		public int $general,			// pts des bases + flottes + commandants
		public int $generalPosition,
		public int $generalVariation,
		public int $experience, 		// experience
		public int $experiencePosition,
		public int $experienceVariation,
		public int $butcher,			// destroyedPEV - lostPEV
		public int $butcherDestroyedPEV,
		public int $butcherLostPEV,
		public int $butcherPosition,
		public int $butcherVariation,
		public int $trader,				// revenu total des routes
		public int $traderPosition,
		public int $traderVariation,
		public int $fight, 				// victoires - défaites
		public int $victories,
		public int $defeat,
		public int $fightPosition,
		public int $fightVariation,
		public int $armies,				// nb de pev total flotte + hangar
		public int $armiesPosition,
		public int $armiesVariation,
		public int $resources, 			// production de ressources par relève (on peut ajouter les recyclages p-e)
		public int $resourcesPosition,
		public int $resourcesVariation,
		public \DateTimeImmutable $createdAt,
	) {
	}
}
