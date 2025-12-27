<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Service\CreditTransaction;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;

readonly class GetCreditHolderStatus
{
	public function __construct(private GetFactionsConfiguration $getFactionsConfiguration)
	{
	}

	public function __invoke(CreditTransaction $creditTransaction, string $part): string
	{
		$creditHolder = match ($part) {
			'receiver' => $creditTransaction->getReceiver(),
			'sender' => $creditTransaction->getSender(),
			default => throw new \InvalidArgumentException('Match case not implemented for this part of transaction'),
		};

		return match (get_class($creditHolder)) {
			Color::class => ($this->getFactionsConfiguration)($creditHolder, 'government'),
			// TODO make a method to get a player status
			Player::class => ($this->getFactionsConfiguration)($creditHolder->faction, 'status')[$creditHolder->status - 1],
			default => throw new \LogicException('Match case not implemented for this kind of CreditHolder'),
		};
	}
}
