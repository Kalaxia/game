<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Organisms;

use App\Modules\Ares\Model\Commander;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ShipTransfer',
	template: 'components/Organisms/Fleet/ShipTransfer.html.twig',
)]
final class ShipTransfer
{
	public Commander $commander;

	public function mount(Commander $commander): void
	{
		$this->commander = $commander;
	}
}
