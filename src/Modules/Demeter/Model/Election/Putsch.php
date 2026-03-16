<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Putsch extends PoliticalEvent
{
	public function __construct(
		public Uuid $id,
		public Color $faction,
		public \DateTimeImmutable $startedAt,
		public ?\DateTimeImmutable $endedAt,
		#[ORM\Column(type: 'boolean', nullable: true)]
		public ?bool $hasSucceeded = null,
	) {
		parent::__construct($id, $faction, $startedAt, $endedAt);
	}

	public function getType(): PoliticalEventType
	{
		return PoliticalEventType::Putsch;
	}
}
