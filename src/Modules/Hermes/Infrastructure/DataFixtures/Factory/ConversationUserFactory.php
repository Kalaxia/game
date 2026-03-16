<?php

declare(ticks=1);

namespace App\Modules\Hermes\Infrastructure\DataFixtures\Factory;

use App\Modules\Hermes\Model\ConversationUser;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ConversationUser>
 */
class ConversationUserFactory extends PersistentObjectFactory
{
	protected function defaults(): array|callable
	{
		return [
			'id' => Uuid::v4(),
			'lastViewedAt' => new \DateTimeImmutable(),
			'playerStatus' => ConversationUser::US_ADMIN,
			'conversationStatus' => ConversationUser::CS_DISPLAY,
		];
	}

	public static function class(): string
	{
		return ConversationUser::class;
	}
}
