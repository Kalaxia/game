<?php

namespace App\Modules\Demeter\Domain\Repository\Law;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Law>
 */
interface LawRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Law;

	/**
	 * @param list<int> $statements
	 *
	 * @return list<Law>
	 */
	public function getByFactionAndStatements(Color $faction, array $statements = []): array;

	public function lawExists(Color $faction, int $type): bool;
}
