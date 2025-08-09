<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Service\CalculateFleetCost;
use App\Modules\Ares\Domain\Service\GetSquadronPev;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Ares\Resource\CommanderResources;
use Doctrine\Common\Util\ClassUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommanderExtension extends AbstractExtension
{
	public function __construct(
		private readonly CalculateFleetCost $calculateFleetCost,
		private readonly CommanderManager $commanderManager,
		private readonly CommanderExperienceHandler $commanderExperienceHandler,
		private readonly CommanderArmyHandler $commanderArmyHandler,
		private readonly GetSquadronPev $getSquadronPev,
	) {
	}

	#[\Override]
    public function getFilters(): array
	{
		return [
			new TwigFilter('mission_label', fn (Commander $commander) => match ($commander->travelType) {
				CommanderMission::Move => 'déplacement vers ' . ($commander->destinationPlace->base?->name ?? 'colonie rebelle'),
				CommanderMission::Loot => 'pillage de ' . ($commander->destinationPlace->base?->name ?? 'colonie rebelle'),
				CommanderMission::Colo => 'colonisation de ' . ($commander->destinationPlace->base?->name ?? 'colonie rebelle'),
				CommanderMission::Back => 'retour vers ' . ($commander->destinationPlace->base?->name ?? 'colonie rebelle'),
				default => 'autre'
			}),
			new TwigFilter('commander_rank', fn (Commander $commander) => $this->getCommanderLevel($commander->level)),
			new TwigFilter('pev', fn (Commander|Squadron $item) => match (ClassUtils::getClass($item)) {
				Commander::class => $this->commanderArmyHandler->getPev($item),
				Squadron::class => ($this->getSquadronPev)($item),
				default => throw new \LogicException(sprintf('Unknown item type %s', ClassUtils::getClass($item))),
			}),
		];
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_commander_level_up_from_report', fn (int $level, int $newExperience) => $this->commanderExperienceHandler->nbLevelUp($level, $newExperience)),
			new TwigFunction('get_commander_missing_experience', fn (Commander $commander) => $this->commanderExperienceHandler->experienceToLevelUp($commander)),
			new TwigFunction('get_fleet_cost', fn (Commander $commander) => ($this->calculateFleetCost)($commander->getNbrShipByType())),
			new TwigFunction('get_commander_position', fn (Commander $commander, int $x1, int $x2, int $y1, int $y2) => $this->commanderManager->getPosition($commander, $x1, $x2, $x2, $y2)),
			new TwigFunction('get_commander_rank', fn (int $level) => $this->getCommanderLevel($level)),
		];
	}

	protected function getCommanderLevel(int $level): string
	{
		return CommanderResources::getInfo($level, 'grade');
	}
}
