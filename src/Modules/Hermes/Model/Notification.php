<?php

namespace App\Modules\Hermes\Model;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Notification
{
	public function __construct(
		public Uuid $id,
		public Player $player,
		public string $title,
		public string $content = '',
		public \DateTimeImmutable $sentAt = new \DateTimeImmutable(),
		public bool $read = false,
		public bool $archived = false,
	) {
	}
}
