<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Event;

use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Model\Player;

interface ConversationMessageEvent
{
	public function getConversation(): Conversation;

	public function getConversationMessageContent(): string;

	public function getConversationMessageAuthor(): Player;
}
