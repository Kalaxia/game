<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

interface TransactionRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Transaction|null;

	public function getLastCompletedTransaction(int $type): Transaction|null;

	public function getProposedTransactions(int $type): array;

	public function getPlayerPropositions(Player $player, int $type): array;

	public function getPlanetPropositions(Planet $planet): array;

	public function getExchangeRate(int $type): float;

	/**
	 * @return Collection<Transaction>
	 */
	public function matchPlayerCompletedTransactionsSince(Player $player, \DateTimeImmutable $completedAt): Collection;
}
