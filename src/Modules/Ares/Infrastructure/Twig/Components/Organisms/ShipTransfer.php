<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Organisms;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Service\Base\Ship\CountMaxStorableShipPoints;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ShipTransfer',
	template: 'components/Organisms/Fleet/ShipTransfer.html.twig',
)]
final class ShipTransfer
{
	public Commander $commander;
	public int $manufactureMaxStorableShipPoints;
	public int $shipyardMaxStorableShipPoints;

	public function __construct(private readonly CountMaxStorableShipPoints $countMaxStorableShipPoints)
	{
	}

	public function mount(Commander $commander): void
	{
		$this->commander = $commander;
		$this->manufactureMaxStorableShipPoints = ($this->countMaxStorableShipPoints)($commander->base, DockType::Manufacture);
		$this->shipyardMaxStorableShipPoints = ($this->countMaxStorableShipPoints)($commander->base, DockType::Shipyard);
	}
}
