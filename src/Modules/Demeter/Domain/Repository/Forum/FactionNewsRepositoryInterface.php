<?php

namespace App\Modules\Demeter\Domain\Repository\Forum;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Forum\FactionNews;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<FactionNews>
 */
interface FactionNewsRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?FactionNews;

	public function getPinnedNew(Color $faction): ?FactionNews;

	/**
	 * @return list<FactionNews>
	 */
	public function getFactionNews(Color $faction): array;

	/**
	 * @return list<FactionNews>
	 */
	public function getFactionBasicNews(Color $faction): array;
}
