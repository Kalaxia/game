<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'factionNews')]
class FactionNews
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Color $faction,
		#[ORM\Column(type: 'string', length: 255)]
		public string $title,
		#[ORM\Column(type: 'text')]
		public string $oContent,
		#[ORM\Column(type: 'text')]
		public string $pContent,
		#[ORM\Column(type: 'boolean')]
		public bool $pinned,
		#[ORM\Column(type: 'smallint')]
		public int $statement,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
	) {
	}
}
