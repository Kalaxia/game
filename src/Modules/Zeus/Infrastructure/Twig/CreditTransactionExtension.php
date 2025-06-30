<?php

namespace App\Modules\Zeus\Infrastructure\Twig;

use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Domain\Service\CreditTransaction\GetCreditHolderName;
use App\Modules\Zeus\Domain\Service\CreditTransaction\GetCreditHolderStatus;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Resource\TutorialResource;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CreditTransactionExtension extends AbstractExtension
{
	public function __construct(
		private readonly GetCreditHolderName $getCreditHolderName,
		private readonly GetCreditHolderStatus $getCreditHolderStatus,
	) {
	}

	#[\Override]
	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_credit_holder_name', fn (CreditTransaction $creditTransaction, string $part): string => ($this->getCreditHolderName)($creditTransaction, $part)),
			new TwigFunction('get_credit_holder_status', fn (CreditTransaction $creditTransaction, string $part): string => ($this->getCreditHolderStatus)($creditTransaction, $part)),
		];
	}
}
