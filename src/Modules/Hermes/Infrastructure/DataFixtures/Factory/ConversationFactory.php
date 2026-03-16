<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\DataFixtures\Factory;

use App\Modules\Hermes\Model\Conversation;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Conversation>
 */
class ConversationFactory extends PersistentObjectFactory
{
	protected function defaults(): array|callable
	{
		return [
			'id' => Uuid::v4(),
			'messagesCount' => 0,
			'createdAt' => new \DateTimeImmutable(),
			'lastMessageAt' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return Conversation::class;
	}
}
