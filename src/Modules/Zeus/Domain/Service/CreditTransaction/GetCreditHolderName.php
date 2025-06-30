<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Service\CreditTransaction;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Util\ClassUtils;

readonly class GetCreditHolderName
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

		return match (ClassUtils::getClass($creditHolder)) {
			Color::class => ($this->getFactionsConfiguration)($creditHolder, 'popularName'),
			Player::class => $creditHolder->name,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}
}
