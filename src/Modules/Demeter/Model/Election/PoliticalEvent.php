<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'politics__events')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', enumType: PoliticalEventType::class)]
#[ORM\DiscriminatorMap([
	PoliticalEventType::DemocraticElection->value => DemocraticElection::class,
	PoliticalEventType::DivineDesignation->value => DivineDesignation::class,
	PoliticalEventType::Putsch->value => Putsch::class,
])]
abstract class PoliticalEvent
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Color $faction,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $startedAt,
		#[ORM\Column(type: 'datetime_immutable', nullable: true)]
		public ?\DateTimeImmutable $endedAt,
	) {
	}

	abstract public function getType(): PoliticalEventType;
}
