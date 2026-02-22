<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Service;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

readonly class GetAvailableAvatars
{
	public function __construct(
		#[Autowire('%kernel.project_dir%')]
		private string $projectDir,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function __invoke(?Color $faction = null): array
	{
		$finder = new Finder();
		$finder->files()->in(sprintf('%s/assets/images/avatars/', $this->projectDir));

		$avatars = array_map(
			fn (\SplFileInfo $avatar) => $avatar->getBasename(),
			iterator_to_array($finder->getIterator()),
		);

		\shuffle($avatars);

		return $avatars;
	}
}
