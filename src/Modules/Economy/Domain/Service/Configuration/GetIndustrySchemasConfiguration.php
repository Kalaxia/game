<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service\Configuration;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Economy\Domain\Enum\ComponentProductSlug;
use App\Modules\Economy\Domain\Enum\ComponentType;
use App\Modules\Economy\Domain\Enum\ResourceType;
use App\Modules\Economy\Domain\Enum\ShipProductSlug;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GetIndustrySchemasConfiguration
{
	public function __construct(
		#[Autowire('%app.industry_schemas%')]
		private array $industrySchemas,
	) {
	}

	/**
	 * @return ($category is 'ships' ? array{
	 *     slug: ShipProductSlug,
	 *     ship_category: ShipCategory,
	 *     requirements: list<array{
	 *		   slug: ComponentProductSlug,
	 *     	   quantity: int,
	 *     }>
	 * } : array{
	 *     slug: ComponentProductSlug,
	 *     type: ComponentType,
	 *     requirements: list<array{
	 *		   type: ResourceType,
	 *         quantity: int,
	 *	   }>
	 * })
	 */
	public function __invoke(string $category, string $slug): array
	{
		return $this->industrySchemas[$category][$slug] ?? throw new \InvalidArgumentException(sprintf(
			'Slug %s for %s category not found',
			$slug,
			$category,
		));
	}
}
