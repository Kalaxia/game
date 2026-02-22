<?php

namespace App\Modules\Athena\Model;

use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class Transaction implements \JsonSerializable
{
	// statement
	public const ST_PROPOSED = 0;		// transaction proposée
	public const ST_COMPLETED = 1;		// transaction terminée
	public const ST_CANCELED = 2;		// transaction annulée
	// type
	public const TYP_RESOURCE = 0;
	public const TYP_SHIP = 1;
	public const TYP_COMMANDER = 2;

	// percentage to cancel an offer
	public const PERCENTAGE_TO_CANCEL = 5;
	// divide price by this constant to find the experience
	public const EXPERIENCE_DIVIDER = 15000;

	// minimum rates for each type
	public const MIN_RATE_RESOURCE = 0.2;
	public const MIN_RATE_SHIP = 1;
	public const MIN_RATE_COMMANDER = 1;

	// maximum rates for each type
	public const MAX_RATE_RESOURCE = 100;
	public const MAX_RATE_SHIP = 100;
	public const MAX_RATE_COMMANDER = 100;

	public function __construct(
		public Uuid $id,
		public Player $player,
		public ?Planet $base,
		public int $type,			// see const TYP_*
		public int $quantity,		// if ($type == TYP_RESOURCE) 	--> resource
		// if ($type == TYP_SHIP) 		--> ship quantity
		// if ($type == TYP_COMMANDER) 	--> experience
		public int $identifier,		// if ($type == TYP_RESOURCE) 	--> NULL
		// if ($type == TYP_SHIP) 		--> shipId
		// if ($type == TYP_COMMANDER) 	--> rCommander
		public \DateTimeImmutable $publishedAt,
		public float $currentRate,
		#[Assert\Expression(
			'this.type == '.self::TYP_COMMANDER.' and value == null',
			message: 'A commander transaction must have a commander set',
		)]
		public ?Commander $commander = null,
		public ?Player $buyer = null,
		public int $sellerFactionFees = 0,
		public int $sellerFactionTaxRate = 0,
		public int $buyerFactionFees = 0,
		public int $buyerFactionTaxRate = 0,
		public int $price = 0,
		public int $commercialShipQuantity = 0,	// ship needed for the transport
		public int $statement = 0,
		public ?\DateTimeImmutable $validatedAt = null,
		// date of acceptance or cancellation
		// 1 resource = x credits (for resources et ships)
		// 1 experience = x credits
	) {
	}

	public function getPriceToCancelOffer(): int
	{
		return intval(floor($this->price * self::PERCENTAGE_TO_CANCEL / 100));
	}

	public function getExperienceEarned(): int
	{
		return 1 + intval(round($this->price / self::EXPERIENCE_DIVIDER));
	}

	public static function getResourcesIcon(int $quantity): int
	{
		if (1000000 <= $quantity && $quantity < 5000000) {
			return 5;
		} elseif (500000 <= $quantity && $quantity < 1000000) {
			return 4;
		} elseif (100000 <= $quantity && $quantity < 500000) {
			return 3;
		} elseif (10000 <= $quantity && $quantity < 100000) {
			return 2;
		}

		return 1;
	}

	public function hasResources(): bool
	{
		return self::TYP_RESOURCE === $this->type;
	}

	public function hasShips(): bool
	{
		return self::TYP_SHIP === $this->type;
	}

	public function hasCommander(): bool
	{
		return self::TYP_COMMANDER === $this->type;
	}

	public function isProposed(): bool
	{
		return self::ST_PROPOSED === $this->statement;
	}

	public function isCompleted(): bool
	{
		return self::ST_COMPLETED === $this->statement;
	}

	public function isCancelled(): bool
	{
		return self::ST_CANCELED === $this->statement;
	}

	public function getTotalPrice(): int
	{
		return $this->price + $this->sellerFactionFees + $this->buyerFactionFees;
	}

	public function getTypeString(): string
	{
		return match ($this->type) {
			self::TYP_RESOURCE => 'resources',
			self::TYP_SHIP => 'ship',
			self::TYP_COMMANDER => 'commander',
		};
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'statement' => $this->statement,
			'player' => $this->player,
			'base' => $this->base,
			'type' => $this->type,
			'quantity' => $this->quantity,
			'identifier' => $this->identifier,
			'commander' => $this->commander,
			'publishedAt' => $this->publishedAt,
			'currentRate' => $this->currentRate,
		];
	}
}
