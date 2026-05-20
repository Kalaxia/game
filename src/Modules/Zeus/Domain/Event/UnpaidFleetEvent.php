<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Event;

use App\Modules\Ares\Model\Commander;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use App\Shared\Domain\Event\TrackingEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class UnpaidFleetEvent implements NotificationEvent, TrackingEvent
{
	public function __construct(
		public PlayerFinancialReport $playerFinancialReport,
		public Commander $commander,
	) {
	}

	public function getTrackingData(): array
	{
		return [
			'ships_cost' => $this->playerFinancialReport->shipsCost,
			'faction_id' => $this->playerFinancialReport->player->faction->identifier,
			'army_ships' => $this->commander->getNbrShipByType(),
			'army_size' => $this->commander->getSizeArmy(),
		];
	}

	public function getTrackingEventName(): string
	{
		return 'Unpaid Fleet';
	}

	public function getTrackingPeopleId(): int
	{
		return $this->playerFinancialReport->player->id;
	}

	public function getNotificationBuilders(TranslatorInterface $translator): \Generator
	{
		yield NotificationBuilder::new()
			->setTitle('Flotte impayée')
			->setContent(NotificationBuilder::paragraph(
				'Vous n\'avez pas assez de crédits pour payer l\'entretien de la flotte de votre officier ',
				$this->commander->name,
				'. Celui-ci a donc déserté ! ... avec la flotte, désolé.',
			))
			->forPlayer($this->playerFinancialReport->player);
	}
}
