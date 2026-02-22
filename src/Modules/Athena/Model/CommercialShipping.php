<?php

namespace App\Modules\Athena\Model;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Model\TravellerInterface;
use Symfony\Component\Uid\Uuid;

class CommercialShipping implements TravellerInterface
{
	// statement
	public const ST_WAITING = 0;		// pret au départ, statique
	public const ST_GOING = 1;			// aller
	public const ST_MOVING_BACK = 2;	// retour

	public const WEDGE = 1000;	// soute

	// attributes
	public function __construct(
		public Uuid $id,
		public ?Player $player,
		public Planet $originBase,
		public ?Planet $destinationBase = null,
		public ?Transaction $transaction = null,
		// @TODO rather have a shipment type and then a quantity field
		public ?int $resourceTransported = null, // soit l'un
		public int $shipQuantity = 0, // soit l'autre
		public int $statement = 0,
		public ?\DateTimeImmutable $departureDate = null,
		public ?\DateTimeImmutable $arrivalDate = null,
	) {
	}

	public function hasResources(): bool
	{
		return (null !== $this->transaction)
			? Transaction::TYP_RESOURCE === $this->transaction->type
			: $this->resourceTransported > 0;
	}

	public function hasShips(): bool
	{
		// Ships sending actually creates a Transaction entity for now
		return (null !== $this->transaction)
			? Transaction::TYP_SHIP === $this->transaction->type
			: false;
	}

	public function hasCommander(): bool
	{
		// Commander sending without transaction is not implemented
		return (null !== $this->transaction)
			? Transaction::TYP_COMMANDER === $this->transaction->type
			: false;
	}

	public function isMoving(): bool
	{
		return self::ST_GOING === $this->statement;
	}

	public function isMovingBack(): bool
	{
		return self::ST_MOVING_BACK === $this->statement;
	}

	public function isWaiting(): bool
	{
		return self::ST_WAITING === $this->statement;
	}

	public function getDepartureDate(): ?\DateTimeImmutable
	{
		return $this->departureDate;
	}

	public function getArrivalDate(): ?\DateTimeImmutable
	{
		return $this->arrivalDate;
	}
}
