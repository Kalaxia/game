<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'politics__candidates')]
class Candidate
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public PoliticalEvent $politicalEvent,
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\Column(type: 'text')]
		public string $program,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public ?Player $chiefChoice = null,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public ?Player $treasurerChoice = null,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public ?Player $warlordChoice = null,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public ?Player $ministerChoice = null,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
	) {
	}
}
