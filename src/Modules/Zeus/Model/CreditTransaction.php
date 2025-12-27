<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;

class CreditTransaction
{
	public function __construct(
		public Uuid $id,
		public Player|null $playerSender,
		public Player|null $playerReceiver,
		public Color|null $factionSender,
		public Color|null $factionReceiver,
		public int $amount,
		public \DateTimeImmutable $createdAt,
		public string|null $comment,
	) {

	}

	public function getReceiver(): CreditHolderInterface
	{
		return $this->playerReceiver ?? $this->factionReceiver;
	}

	public function getSender(): CreditHolderInterface
	{
		return $this->playerSender ?? $this->factionSender;
	}
}
