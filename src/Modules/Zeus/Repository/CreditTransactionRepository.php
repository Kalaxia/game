<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

class CreditTransactionRepository extends DoctrineRepository implements CreditTransactionRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CreditTransaction::class);
	}

	public function getAllBySender(CreditHolderInterface $sender): array
	{
		return $this->findBy(
			match (get_class($sender)) {
				Color::class => ['factionSender' => $sender],
				Player::class => ['playerSender' => $sender],
				default => throw new \RuntimeException('Match case not implemented'),
			},
			[
				'createdAt' => 'DESC',
			],
			20,
		);
	}

	public function getAllByPlayerReceiver(Player $player): array
	{
		return $this->findBy([
			'playerReceiver' => $player,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function getAllByFactionReceiverFromMembers(Color $faction): array
	{
		return $this->findBy([
			'factionReceiver' => $faction,
			'factionSender' => null,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function getAllByFactionReceiverFromFactions(Color $faction): array
	{
		return $this->findBy([
			'factionReceiver' => $faction,
			'playerSender' => null,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function matchAllByPlayerSince(Player $player, \DateTimeImmutable $since): Collection
	{
		return $this->matching(new Criteria(
			Criteria::expr()->andX(
				Criteria::expr()->orX(
					Criteria::expr()->eq('playerReceiver', $player),
					Criteria::expr()->eq('playerSender', $player),
				),
				Criteria::expr()->gte('createdAt', $since),
			)
		));
	}
}
