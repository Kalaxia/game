<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use App\Classes\Library\Format;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'MandateDashboard',
	template: 'components/Faction/Organisms/MandateDashboard.html.twig'
)]
class MandateDashboard
{
	public Mandate $currentMandate;

	public function __construct(
		private readonly ClockInterface $clock,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function getPercent(): int
	{
		$mandateDuration = $this->durationHandler->getDiff(
			$this->currentMandate->startedAt,
			$this->currentMandate->expiredAt,
		);
		$spentTime = $this->durationHandler->getDiff(
			$this->currentMandate->startedAt,
			$this->clock->now(),
		);

		return intval(Format::percent(
			$spentTime,
			$mandateDuration,
		));
	}
}
