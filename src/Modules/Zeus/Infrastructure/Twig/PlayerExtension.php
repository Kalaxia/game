<?php

namespace App\Modules\Zeus\Infrastructure\Twig;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Gaia\Manager\PlanetManager;
use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PlayerExtension extends AbstractExtension
{
	public function __construct(
		private readonly BonusApplierInterface      $bonusApplier,
		private readonly CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private readonly DurationHandler            $durationHandler,
		private readonly GetFactionsConfiguration   $getFactionsConfiguration,
		private readonly PlanetManager              $planetManager,
		private readonly ResearchHelper             $researchHelper,
		private readonly int                        $timeEventUpdate,
		private readonly int                        $allyInactiveTime,
	) {
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('apply_player_bonus', fn (int|float $initialValue, int $playerBonusId) => $this->bonusApplier->apply($initialValue, $playerBonusId)),
			new TwigFunction('get_player_bonus', fn (int $playerBonusId) => $this->currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get($playerBonusId)),
			new TwigFunction('get_faction_info', fn (int $factionId, string $info) => ($this->getFactionsConfiguration)($factionId, $info)),
			new TwigFunction('get_player_planets_count', fn (array $movingCommanders) => $this->planetManager->countPlayerPlanets($movingCommanders)),
			new TwigFunction('get_research_info', fn (string $researchType, string $info) => $this->researchHelper->getInfo($researchType, $info)),
			new TwigFunction('is_player_online', fn (Player $player) => $this->durationHandler->getDiff(new \DateTimeImmutable(), $player->dLastActivity) < ($this->timeEventUpdate * 2)),
			new TwigFunction('is_player_inactive', fn (Player $player) => $this->durationHandler->getHoursDiff(new \DateTimeImmutable(), $player->dLastActivity) > $this->allyInactiveTime),
		];
	}
}
