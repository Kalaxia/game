<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Service\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GetIndustrySchemasConfiguration
{
	public function __construct(
		#[Autowire('%app.industry_schemas%')]
		private array $industrySchemas,
	) {
	}

	public function __invoke(string $category, string $slug): mixed
	{
		return $this->industrySchemas[$category][$slug] ?? throw new \InvalidArgumentException(sprintf(
			'Slug %s for %s category not found',
			$slug,
			$category,
		));
	}
}
