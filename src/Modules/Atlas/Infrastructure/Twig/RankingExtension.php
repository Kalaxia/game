<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Infrastructure\Twig;

use App\Modules\Atlas\Model\PlayerRanking;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RankingExtension extends AbstractExtension
{
	#[\Override]
	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_player_ranking_data_by_type', fn (PlayerRanking $playerRanking, string $type) => match ($type) {
				'general' => [
					'value' => $playerRanking->general,
					'position' => $playerRanking->generalPosition,
					'variation' => $playerRanking->generalVariation,
				],
				'resources' => [
					'value' => $playerRanking->resources,
					'position' => $playerRanking->resourcesPosition,
					'variation' => $playerRanking->resourcesVariation,
				],
				'xp' => [
					'value' => $playerRanking->experience,
					'position' => $playerRanking->experiencePosition,
					'variation' => $playerRanking->experienceVariation,
				],
				'fight' => [
					'value' => $playerRanking->fight,
					'position' => $playerRanking->fightPosition,
					'variation' => $playerRanking->fightVariation,
				],
				'armies' => [
					'value' => $playerRanking->armies,
					'position' => $playerRanking->armiesPosition,
					'variation' => $playerRanking->armiesVariation,
				],
				'butcher' => [
					'value' => $playerRanking->butcher,
					'position' => $playerRanking->butcherPosition,
					'variation' => $playerRanking->butcherVariation,
				],
				'trader' => [
					'value' => $playerRanking->trader,
					'position' => $playerRanking->traderPosition,
					'variation' => $playerRanking->traderVariation,
				],
				default => throw new \InvalidArgumentException(sprintf('%s is not a valid ranking type', $type)),
			}),
		];
	}
}
