<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event\Government;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\TrackingEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class NewMinisterEvent implements NewsEvent, NotificationEvent, TrackingEvent
{
	public function __construct(
		public Player $leader,
		public Player $newMinister,
		public ?Player $predecessor = null,
	) {
	}

	public function getTrackingPeopleId(): ?int
	{
		return $this->leader->id;
	}

	public function getTrackingEventName(): string
	{
		return 'New Minister';
	}

	public function getTrackingData(): array
	{
		return [
			'faction_identifier' => $this->leader->faction->identifier,
			'ministry' => $this->newMinister->status,
			'minister_id' => $this->newMinister?->id,
			'predecessor_id' => $this->predecessor?->id,
		];
	}

	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator {
		yield NotificationBuilder::new()
			->setTitle('Nomination au gouvernement')
			->setContent(NotificationBuilder::paragraph(
				'Vous avez été choisi pour devenir ',
				$translator->trans(
					sprintf(
						'factions.%d.status.%d',
						$this->newMinister->faction->identifier,
						$this->newMinister->status->value,
					),
					parameters: ['gender' => $this->newMinister->getGender()],
				),
				' de votre faction.'
			))
			->forPlayer($this->newMinister);

		if (null !== $this->predecessor) {
			yield NotificationBuilder::new()
				->setTitle('Eviction du gouvernement')
				->setContent(NotificationBuilder::paragraph(
					'Vous avez été renvoyé du poste de ',
					$translator->trans(sprintf(
						'factions.%d.status.%d',
						$this->predecessor->faction->identifier,
						$this->predecessor->status->value,
					), ['gender' => $this->predecessor->getGender()]),
					' de votre faction.',
				))
				->forPlayer($this->predecessor);
		}
	}

	public function getNewsBuilders(array $factions): \Generator
	{
		$data = [
			'ministerGender' => $this->newMinister->getGender(),
			'ministerName' => $this->newMinister->name,
			'ministerStatus' => $this->newMinister->status,
			'leaderGender' => $this->leader->getGender(),
			'leaderStatus' => $this->leader->status,
			'leaderName' => $this->leader->name,
		];

		if (null !== $this->predecessor) {
			$data['predecessorGender'] = $this->predecessor->getGender();
			$data['predecessorStatus'] = $this->predecessor->status;
			$data['predecessorName'] = $this->predecessor->name;
		}

		yield NewsBuilder::new()
			->fromFaction($this->leader->faction)
			->forFaction($this->leader->faction)
			->setType(NewsType::NewMinister)
			->setData($data);

		yield NewsBuilder::new()
			->fromFaction($this->leader->faction)
			->forFactions(array_filter(
				$factions,
				fn (Color $faction) => $faction->identifier !== $this->newMinister->faction->identifier,
			))
			->setType(NewsType::ForeignNewMinister)
			->setData($data);
	}
}
