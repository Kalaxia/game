<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Layout;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'SidebarItem',
	template: 'components/Molecules/Layout/SidebarItem.html.twig',
)]
class SidebarItem
{
	public string $route;
	public array $params = [];
	public ?string $picto = null;
	public string $label;
	public ?int $badgeNumber = null;
}
