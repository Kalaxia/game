<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules;

use App\Modules\Galaxy\Resource\PlaceResource;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'BaseTypeChoice',
	template: 'components/Molecules/Base/BaseTypeChoice.html.twig'
)]
class BaseTypeChoice
{
	public int $type;

	public function getName(): string
	{
		return PlaceResource::get($this->type, 'name');
	}

	public function getFleetQuantity(): int
	{
		return PlaceResource::get($this->type, 'l-line') + PlaceResource::get($this->type, 'r-line');
	}

	public function getTaxToll(): int
	{
		return intval(PlaceResource::get($this->type, 'tax') * 100);
	}
}
