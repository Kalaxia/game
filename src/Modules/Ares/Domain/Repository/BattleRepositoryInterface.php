<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Battle>
 */
interface BattleRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Battle;
}
