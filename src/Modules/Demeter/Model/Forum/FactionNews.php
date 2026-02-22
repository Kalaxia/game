<?php

namespace App\Modules\Demeter\Model\Forum;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class FactionNews
{
	public function __construct(
		public Uuid $id,
		public Color $faction,
		public string $title,
		public string $oContent,
		public string $pContent,
		public bool $pinned,
		public int $statement,
		public \DateTimeImmutable $createdAt,
	) {
	}
}
