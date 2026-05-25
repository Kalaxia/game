<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\Persister;

use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Domain\Repository\NewsRepositoryInterface;

final readonly class NewsPersister
{
	public function __construct(
		private NewsRepositoryInterface $newsRepository,
	) {
	}

	public function saveFromBuilder(NewsBuilder $builder): void
	{
		foreach ($builder->build() as $news) {
			$this->newsRepository->save($news);
		}
	}
}
