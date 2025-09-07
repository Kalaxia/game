<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Twig\Components\Organisms;

use App\Modules\Ares\Model\Report;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Galaxy\Domain\Entity\Place;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'PlaceSummary',
	template: 'components/Organisms/Map/PlaceSummary.html.twig',
)]
class PlaceSummary
{
	public Place $place;
	/** @var list<Report> */
	public array $combatReports;
	/** @var list<SpyReport> */
	public array $spyReports;
}
