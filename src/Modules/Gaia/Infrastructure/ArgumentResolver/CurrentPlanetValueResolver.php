<?php

namespace App\Modules\Gaia\Infrastructure\ArgumentResolver;

use App\Modules\Gaia\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Gaia\Domain\Entity\Planet;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTaggedItem(index: 'current_planet', priority: 150)]
readonly class CurrentPlanetValueResolver implements ValueResolverInterface
{
	public function __construct(private CurrentPlayerPlanetsRegistry $currentPlayerBasesRegistry)
	{
	}

	public function resolve(Request $request, ArgumentMetadata $argument): array
	{
		if (Planet::class !== $argument->getType()) {
			return [];
		}
		if (null === $this->currentPlayerBasesRegistry->current()) {
			return [];
		}

		return [$this->currentPlayerBasesRegistry->current()];
	}
}
