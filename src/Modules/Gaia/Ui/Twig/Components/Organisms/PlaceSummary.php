<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Ui\Twig\Components\Organisms;

use App\Modules\Ares\Model\Report;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Gaia\Domain\Entity\Planet;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlaceSummary',
	template: 'components/Organisms/Map/PlaceSummary.html.twig',
)]
class PlaceSummary
{
	public Planet $place;
	/** @var list<Report> */
	public array $combatReports;
	/** @var list<SpyReport> */
	public array $spyReports;
}
