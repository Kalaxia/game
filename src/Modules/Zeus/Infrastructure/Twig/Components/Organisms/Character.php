<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Twig\Components\Organisms;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Character',
	template: 'components/Organisms/Player/Character.html.twig',
)]
final class Character
{
	public Player $player;
	public int $playerMissingExperience;
	public int $playerNextLevelExperience;
	public float $playerExperienceProgress;

	public function __construct(
		#[Autowire('%zeus.player.base_level%')]
		private readonly int $baseLevelPlayer,
	) {
	}

	public function mount(Player $player): void
	{
		$this->player = $player;
		$this->playerMissingExperience = intval($this->baseLevelPlayer * (2 ** ($player->level - 1)));
		// @TODO Not quite sure that this is the next experience level. To check and rename accordingly
		$this->playerNextLevelExperience = intval($this->baseLevelPlayer * (2 ** ($player->level - 2)));
		$this->playerExperienceProgress = ((($player->experience - $this->playerNextLevelExperience) * 200) / $this->playerMissingExperience);
	}
}
