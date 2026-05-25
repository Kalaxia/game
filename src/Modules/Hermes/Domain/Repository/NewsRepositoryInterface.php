<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Domain\Entity\News;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<News>
 */
interface NewsRepositoryInterface extends EntityRepositoryInterface
{
	public function countUnreadNewsForPlayer(Player $player): int;

	/**
	 * @return list<News>
	 */
	public function getAll(Color $faction, int $limit = 30, int $offset = 0): array;
}
