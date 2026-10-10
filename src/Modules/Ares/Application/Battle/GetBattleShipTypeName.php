<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Display name of a battle ship type: the ship category name, or its own translation for new types.
 */
readonly class GetBattleShipTypeName
{
	public function __construct(
		private TranslatorInterface $translator,
	) {
	}

	public function __invoke(BattleShipType $type): string
	{
		return $this->translator->trans(null !== $type->shipCategory
			? sprintf('ship_categories.%d.name', $type->shipCategory->value)
			: sprintf('battle_ship_types.%s.name', $type->identifier));
	}
}
