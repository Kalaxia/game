<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Organisms;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
	name: 'FleetGrid',
	template: 'components/Organisms/Fleet/Grid.html.twig',
)]
class FleetGrid
{
	use DefaultActionTrait;

	#[LiveProp(updateFromParent: true)]
	public int $faction;
	public ?int $selectedX = null;
	public ?int $selectedY = null;

	#[LiveListener('gridPositionSelected')]
	public function onGridPositionSelected(
		#[LiveArg]
		int $x,
		#[LiveArg]
		int $y,
	): void {
		$this->selectedX = $x;
		$this->selectedY = $y;
	}
}
