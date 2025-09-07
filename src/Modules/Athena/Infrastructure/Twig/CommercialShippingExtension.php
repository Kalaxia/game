<?php

namespace App\Modules\Athena\Infrastructure\Twig;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommercialShippingExtension extends AbstractExtension
{
	public function __construct(protected TransactionManager $transactionManager)
	{
	}

	#[\Override]
    public function getFilters(): array
	{
		return [
			new TwigFilter('commercial_shipping_picto', fn (CommercialShipping $commercialShipping) => Transaction::getResourcesIcon($commercialShipping->transaction?->quantity ?? $commercialShipping->resourceTransported)),
			new TwigFilter('transaction_picto', fn (Transaction $transaction) => Transaction::getResourcesIcon($transaction->quantity)),
		];
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_min_price', fn (string $transactionType, int $quantity, ?int $identifier = null) => Game::getMinPriceRelativeToRate($transactionType, 1, $identifier)),
			new TwigFunction('get_transaction_class', fn (CommercialShipping $commercialShipping) => match ($commercialShipping->transaction?->type) {
				Transaction::TYP_RESOURCE => 'resources',
				Transaction::TYP_COMMANDER => 'commander',
				Transaction::TYP_SHIP => 'ship',
				default => null,
			}),
			new TwigFunction('get_transaction_type', fn (Transaction $transaction) => match ($transaction->type) {
				Transaction::TYP_RESOURCE => 'resources',
				Transaction::TYP_COMMANDER => 'commander',
				Transaction::TYP_SHIP => 'ship',
				default => null,
			}),
			new TwigFunction('get_cancellation_price', fn (CommercialShipping $commercialShipping) => Format::number(floor($commercialShipping->transaction->price * Transaction::PERCENTAGE_TO_CANCEL / 100))),
			new TwigFunction('get_transaction_data', fn (Transaction $transaction, Planet $planet, float $currentRate) => $this->transactionManager->getTransactionData($transaction, $planet, $currentRate)),
		];
	}
}
