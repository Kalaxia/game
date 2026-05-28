<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Molecules;

use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use App\Modules\Zeus\Model\Player;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'MinistryCard',
	template: 'components/Faction/Molecules/MinistryCard.html.twig'
)]
final class MinistryCard
{
	public Color $faction;
	public PlayerStatus $status;
	public ?Player $minister = null;
	public array $senators = [];
}
