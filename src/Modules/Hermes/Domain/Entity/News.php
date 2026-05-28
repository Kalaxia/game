<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Entity;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;

#[ORM\Entity]
#[ORM\Table(name: 'communication__news')]
class News implements TranslatableInterface
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Color $source,
		#[ORM\ManyToOne]
		public Color $recipient,
		#[ORM\Column(length: 96, enumType: NewsType::class)]
		public NewsType $type,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
		#[ORM\Column(type: 'json')]
		public array $data = [],
	) {
	}

	public function getId(): Uuid
	{
		return $this->id;
	}

	public function setId(Uuid $id): static
	{
		$this->id = $id;

		return $this;
	}

	public function getSource(): Color
	{
		return $this->source;
	}

	public function setSource(Color $source): static
	{
		$this->source = $source;

		return $this;
	}

	public function getRecipient(): Color
	{
		return $this->recipient;
	}

	public function setRecipient(Color $recipient): static
	{
		$this->recipient = $recipient;

		return $this;
	}

	public function getType(): NewsType
	{
		return $this->type;
	}

	public function setType(NewsType $type): static
	{
		$this->type = $type;

		return $this;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeImmutable $createdAt): static
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): static
	{
		$this->data = $data;

		return $this;
	}

	public function trans(TranslatorInterface $translator, ?string $locale = null): string
	{
		$translateStatus = fn (PlayerStatus|int $status, string $gender): string => $translator->trans(
			sprintf(
				'factions.%d.status.%d',
				$this->source->identifier,
				is_int($status) ? $status : $status->value,
			),
			parameters: ['gender' => $gender],
			locale: $locale,
		);

		return $translator->trans(
			sprintf('news.%s.content', $this->type->value),
			array_merge($this->data, match ($this->type) {
				NewsType::PutschAttempt => [
					'playerStatus' => $translateStatus($this->data['playerStatus'], $this->data['playerGender']),
					'complement' => isset($this->data['leaderName'])
						? t('news.putsch_attempt.complement', array_merge($this->data, [
							'leaderName' => $this->data['leaderName'],
						]))
						: '',
				],
				NewsType::NewCandidate => [
					'otherCandidatesString' => implode(', ', array_map(
						fn (array $candidate) => $translateStatus($candidate['status'], $candidate['gender']).' '.$candidate['name'],
						$this->data['otherCandidates'],
					)),
				],
				NewsType::NewLeader => [
					'leaderStatus' => $translateStatus(PlayerStatus::Chief, $this->data['leaderGender']),
				],
				NewsType::FiredMinister => [
					'ministerStatus' => $translateStatus($this->data['ministerStatus'], $this->data['ministerGender']),
					'leaderStatus' => $translateStatus(PlayerStatus::Chief, $this->data['leaderGender']),
				],
				NewsType::NewMinister => [
					'ministerStatus' => $translateStatus($this->data['ministerStatus'], $this->data['ministerGender']),
					'leaderStatus' => $translateStatus(PlayerStatus::Chief, $this->data['leaderGender']),
					'complement' => isset($this->data['predecessorName'])
						? $translator->trans('news.new_minister.complement', array_merge($this->data, [
							'predecessorStatus' => $translateStatus($this->data['predecessorStatus'], $this->data['predecessorGender']),
							'eliteStatus' => $translateStatus(PlayerStatus::Parliament, $this->data['predecessorGender']),
						]))
						: '',
				],
				NewsType::ForeignNewCandidate => [
					'otherCandidatesString' => implode(', ', array_map(
						fn (array $candidate) => $translateStatus($candidate['status'], $candidate['gender']).' '.$candidate['name'],
						$this->data['otherCandidates'],
					)),
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->data['factionIdentifier'],
					)),
				],
				NewsType::ForeignNewMinister => [
					'ministerStatus' => $translateStatus($this->data['ministerStatus'], $this->data['ministerGender']),
					'leaderStatus' => $translateStatus(PlayerStatus::Chief, $this->data['leaderGender']),
					'complement' => isset($this->data['predecessorName'])
						? $translator->trans('news.foreign_new_minister.complement', array_merge($this->data, [
							'predecessorStatus' => $translateStatus($this->data['predecessorStatus'], $this->data['predecessorGender']),
							'eliteStatus' => $translateStatus(PlayerStatus::Parliament, $this->data['predecessorGender']),
						]))
						: '',
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->source->identifier,
					)),
				],
				NewsType::ForeignFiredMinister => [
					'ministerStatus' => $translateStatus($this->data['ministerStatus'], $this->data['ministerGender']),
					'leaderStatus' => $translateStatus(PlayerStatus::Chief, $this->data['leaderGender']),
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->source->identifier,
					)),
				],
				NewsType::ForeignPutschAttempt => [
					'playerStatus' => $translateStatus($this->data['playerStatus'], $this->data['playerGender']),
					'complement' => isset($this->data['leaderName'])
						? $translator->trans('news.foreign_putsch_attempt.complement', array_merge($this->data, [
							'leaderName' => $this->data['leaderName'],
							'factionPopularName' => $translator->trans(sprintf(
								'factions.%d.name.popular',
								$this->data['factionIdentifier'],
							)),
						]))
						: '',
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->data['factionIdentifier'],
					)),
				],
				NewsType::ForeignNewLeader => [
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->data['factionIdentifier'],
					)),
					'leaderStatus' => $translateStatus(PlayerStatus::Chief, $this->data['leaderGender']),
				],
				NewsType::SectorLost => [
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->data['loserIdentifier'],
					)),
					'complement' => null !== $this->data['winnerIdentifier']
						? $translator->trans('news.sector_lost.winnerComplement', [
							'winnerPopularName' => $translator->trans(sprintf(
								'factions.%d.name.popular',
								$this->data['winnerIdentifier'],
							)),
						])
						: '',
				],
				NewsType::SectorWon => [
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->data['winnerIdentifier'],
					)),
					'complement' => null !== $this->data['loserIdentifier']
						? $translator->trans('news.sector_won.loserComplement', [
							'loserOfficialName' => $translator->trans(sprintf(
								'factions.%d.name.official',
								$this->data['loserIdentifier'],
							)),
						])
						: '',
				],
				NewsType::SectorOwnerChange => [
					'factionPopularName' => $translator->trans(sprintf(
						'factions.%d.name.popular',
						$this->data['winnerIdentifier'],
					)),
					'loserComplement' => null !== $this->data['loserIdentifier']
						? $translator->trans('news.sector_owner_change.loserComplement', [
							'loserPopularName' => $translator->trans(sprintf(
								'factions.%d.name.popular',
								$this->data['loserIdentifier'],
							)),
						])
						: '',
					'noWinnerComplement' => null === $this->data['winnerIdentifier']
						? $translator->trans('news.sector_owner_change.noWinnerComplement')
						: '',
					'winnerComplement' => null !== $this->data['winnerIdentifier']
						? $translator->trans('news.sector_owner_change.winnerComplement', [
							'winnerPopularName' => $translator->trans(sprintf(
								'factions.%d.name.popular',
								$this->data['winnerIdentifier'],
							)),
						])
						: '',
				],
				default => [],
			}),
		);
	}
}
