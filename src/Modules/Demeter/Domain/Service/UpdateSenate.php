<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;

readonly class UpdateSenate
{
	public function __construct(
		private EntityManagerInterface $entityManager,
		private NotificationRepositoryInterface $notificationRepository,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(Color $faction): void
	{
		$factionPlayers = $this->playerRepository->getFactionPlayersByRanking($faction);
		$seatsCount = $this->countAvailableSeats(count($factionPlayers));

		$senatePromoteNotificationBuilder = NotificationBuilder::new()
			// TODO genders
			->setTitle('Vous êtes sénateur')
			->setContent(NotificationBuilder::paragraph(
				'Vos actions vous ont fait gagner assez de prestige pour faire partie du sénat.',
			));

		$senateDemoteNotificationBuilder = NotificationBuilder::new()
			->setTitle('Vous n\'êtes plus sénateur')
			->setContent(NotificationBuilder::paragraph(
				'Vous n\'avez plus assez de prestige pour rester dans le sénat.'
			));

		foreach ($factionPlayers as $key => $factionPlayer) {
			if ($factionPlayer->isGovernmentMember()) {
				continue;
			}
			if ($key < $seatsCount) {
				if (!$factionPlayer->isParliamentMember()) {
					$this->notificationRepository->save($senatePromoteNotificationBuilder->for($factionPlayer));
				}
				$factionPlayer->status = Player::PARLIAMENT;
			} else {
				if ($factionPlayer->isParliamentMember()) {
					$this->notificationRepository->save($senateDemoteNotificationBuilder->for($factionPlayer));
				}
				// TODO handle ministers
				$factionPlayer->status = Player::STANDARD;
			}
		}
		$this->entityManager->flush();
	}

	private function countAvailableSeats(int $membersCount): int
	{
		$seatsCount = intval(round($membersCount / 4));
		// If there is less than 40 players in a faction, the seats count is up to 10 senators
		if ($seatsCount < 10) {
			$seatsCount = 10;
		}
		// If there is more than 120 players in a faction, the seats count is up to 40 senators
		if ($seatsCount > 40) {
			$seatsCount = 40;
		}

		return $seatsCount;
	}
}
