<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event\Government;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\TrackingEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FiredMinisterEvent implements NewsEvent, NotificationEvent, TrackingEvent
{
	public function __construct(
		public Player $leader,
		public Player $minister,
		public PlayerStatus $status,
	) {
	}

	public function getTrackingPeopleId(): ?int
	{
		return $this->leader->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Fired Minister';
	}

	public function getTrackingData(): array
	{
		return [
			'faction_identifier' => $this->leader->faction->identifier,
			'ministry' => $this->status,
			'minister_id' => $this->minister->id,
		];
	}

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Eviction du gouvernement')
			->setContent(NotificationBuilder::paragraph(
				'Vous avez été renvoyé du poste de ',
				$translator->trans(sprintf(
					'factions.%d.status.%d',
					$this->minister->faction->identifier,
					$this->minister->status->value,
				), ['gender' => $this->minister->getGender()]),
				' de votre faction.',
			))
			->forPlayer($this->minister);
	}

	public function getNewsBuilders(array $factions): \Generator
	{
		$data = [
			'ministerGender' => $this->minister->getGender(),
			'ministerName' => $this->minister->name,
			'ministerStatus' => $this->minister->status,
			'leaderGender' => $this->leader->getGender(),
			'leaderStatus' => $this->leader->status,
			'leaderName' => $this->leader->name,
		];

		yield NewsBuilder::new()
			->fromFaction($this->leader->faction)
			->forFaction($this->leader->faction)
			->setType(NewsType::FiredMinister)
			->setData($data);

		yield NewsBuilder::new()
			->fromFaction($this->leader->faction)
			->forFactions(array_filter(
				$factions,
				fn (Color $faction) => $faction->identifier !== $this->minister->faction->identifier,
			))
			->setType(NewsType::ForeignFiredMinister)
			->setData($data);
	}
}
