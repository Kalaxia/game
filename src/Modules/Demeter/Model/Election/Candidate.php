<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'candidate')]
class Candidate
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Election $election,
		#[ORM\ManyToOne]
		public Player $player,
		#[ORM\Column(type: 'text')]
		public string $program,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public Player|null $chiefChoice = null,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public Player|null $treasurerChoice = null,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public Player|null $warlordChoice = null,
		#[ORM\ManyToOne]
		#[ORM\JoinColumn(nullable: true)]
		public Player|null $ministerChoice = null,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
	) {
	}
}
