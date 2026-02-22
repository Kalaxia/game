<?php

namespace App\Modules\Hermes\Model;

use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Conversation
{
	public const CONVERSATION_BY_PAGE = 25;

	public const TY_USER = 1;
	public const TY_SYSTEM = 2;

	public function __construct(
		public Uuid $id,
		public \DateTimeImmutable $createdAt,
		public ?string $title = null,
		public ?\DateTimeImmutable $lastMessageAt = null,
		public int $messagesCount = 0,
		public int $type = self::TY_USER,
		/** @var Collection<ConversationUser> $players */
		public Collection $players = new ArrayCollection(),
	) {
	}

	public function hasPlayer(Player $player): bool
	{
		foreach ($this->players as $conversationUser) {
			if ($conversationUser->player->id === $player->id) {
				return true;
			}
		}

		return false;
	}

	public function getPlayerPart(Player $player): ConversationUser
	{
		foreach ($this->players as $conversationUser) {
			if ($conversationUser->player->id === $player->id) {
				return $conversationUser;
			}
		}
		throw new \RuntimeException('Player not part of this conversation');
	}

	public function getInitiator(): Player
	{
		foreach ($this->players as $conversationUser) {
			if (ConversationUser::US_ADMIN === $conversationUser->playerStatus) {
				return $conversationUser->player;
			}
		}
		throw new \RuntimeException(sprintf('There is no initiator for conversation %s', $this->id->toRfc4122()));
	}

	public function isGroupConversation(): bool
	{
		return 2 > $this->players->count();
	}

	public function getLastPage(): int
	{
		return intval(ceil($this->messagesCount / ConversationMessage::MESSAGE_BY_PAGE));
	}
}
