<?php

namespace App\Modules\Demeter\Infrastructure\Twig;

use App\Modules\Demeter\Resource\ForumResources;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ForumExtension extends AbstractExtension
{
	public function __construct(
	) {
	}

	#[\Override]
	public function getFunctions(): array
	{
		return [
			// @TODO move get_faction_info here and replace these methods
			new TwigFunction('get_forum_info', fn (int $forumId, string $info) => ForumResources::getInfoForId($forumId, $info)),
		];
	}
}
