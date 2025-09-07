<?php

namespace App\Modules\Galaxy\Infrastructure\ArgumentResolver;

use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Galaxy\Domain\Entity\Planet;
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
