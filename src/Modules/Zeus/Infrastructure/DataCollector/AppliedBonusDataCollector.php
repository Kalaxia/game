<?php

namespace App\Modules\Zeus\Infrastructure\DataCollector;

use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Application\Handler\Bonus\TraceableBonusApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppliedBonusDataCollector extends AbstractDataCollector
{
	public function __construct(
		private readonly BonusApplierInterface $bonusApplier,
		private readonly CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
	) {
	}

	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
	{
		if (!$this->bonusApplier instanceof TraceableBonusApplier) {
			return;
		}

		$this->data = [
			'applied_modifiers' => $this->bonusApplier->getTracedBonuses(),
			'player_bonuses' => $this->currentPlayerBonusRegistry->getPlayerBonus()?->bonuses->all(),
		];
	}

	public static function getTemplate(): ?string
	{
		return 'components/data_collector/applied_bonuses.html.twig';
	}

	public function getAppliedBonuses(): array
	{
		return $this->data['applied_modifiers'] ?? [];
	}

	public function getPlayerBonuses(): array
	{
		return $this->data['player_bonuses'] ?? [];
	}
}
