<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Model\Color;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PutschDashboard',
	template: 'components/Faction/Organisms/PutschDashboard.html.twig',
)]
class PutschDashboard
{
	public Color $faction;
	public int $remainingPutschTime;

	public function __construct(
		private readonly NextElectionDateCalculator $nextElectionDateCalculator,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function mount(Color $faction): void
	{
		$this->remainingPutschTime = $this->durationHandler->getRemainingTime(
			$this->nextElectionDateCalculator->getPutschEndDate($faction),
		);
	}
}
