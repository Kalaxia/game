<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class CreditTransaction
{
	public function __construct(
		public Uuid $id,
		public ?Player $playerSender,
		public ?Player $playerReceiver,
		public ?Color $factionSender,
		public ?Color $factionReceiver,
		public int $amount,
		public \DateTimeImmutable $createdAt,
		public ?string $comment,
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
