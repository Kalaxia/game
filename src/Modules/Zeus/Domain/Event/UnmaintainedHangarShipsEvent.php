<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Event;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use App\Shared\Domain\Event\TrackingEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class UnmaintainedHangarShipsEvent implements NotificationEvent, TrackingEvent
{
	public function __construct(
		public PlayerFinancialReport $playerFinancialReport,
		public Planet $planet,
		public int $shipIdentifier,
		public int $shipsToKill,
	) {
	}

	public function getTrackingEventName(): string
	{
		return 'Unmaintained Hangar Ships';
	}

	public function getTrackingData(): array
	{
		return [
			'faction_id' => $this->playerFinancialReport->player->faction->id,
			'ships_cost' => $this->playerFinancialReport->shipsCost,
			'planet_id' => $this->planet->id,
			'ship_id' => $this->shipIdentifier,
			'ships_to_kill' => $this->shipsToKill,
		];
	}

	public function getTrackingPeopleId(): int
	{
		return $this->playerFinancialReport->player->id;
	}

	public function getNotificationBuilders(TranslatorInterface $translator): \Generator
	{
		yield NotificationBuilder::new()
			->setTitle('Entretien vaisseau impayé')
			->setContent(NotificationBuilder::paragraph(
				'Domaine',
				NotificationBuilder::divider(),
				'Vous n\'avez pas assez de crédits pour payer l\'entretien',
				(1 === $this->shipsToKill)
					? sprintf(
					' d\'un(e) %s sur %s. Ce vaisseau part donc à la casse ! ',
					$translator->trans(sprintf('ship_categories.%s.name', $this->shipIdentifier)),
					$this->planet->name,
				)
					: sprintf(
					' de %d %ss sur %s. Ces vaisseaux partent donc à la casse !',
					$this->shipsToKill,
					$translator->trans(sprintf('ship_categories.%s.name', $this->shipIdentifier)),
					$this->planet->name,
				)
			))
			->forPlayer($this->playerFinancialReport->player);
	}
}
