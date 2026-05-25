<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Twig\Components\Atoms;

use App\Modules\Zeus\Model\Player;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlayerStatus',
	template: 'components/Atoms/Player/PlayerStatus.html.twig',
)]
final class PlayerStatus
{
	public Player $player;
}
