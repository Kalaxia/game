<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'politics__mandates')]
class Mandate
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Color $faction,
		#[ORM\ManyToOne]
		public Player $leader,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $startedAt,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $expiredAt,
		#[ORM\OneToOne]
		#[ORM\JoinColumn(nullable: true)]
		public ?PoliticalEvent $election = null,
	) {
	}
}
