<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Molecules;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Util\ClassUtils;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CreditTransactionHistory',
	template: 'components/Faction/Molecules/CreditTransactionHistory.html.twig',
)]
class CreditTransactionHistory
{
	public CreditTransaction $transaction;
	public string $side;

	public function __construct(
		private readonly GetFactionsConfiguration $getFactionsConfiguration,
	) {
	}

	public function displayLink(): string
	{
		return $this->side === 'sender' ? $this->getSenderLink() : $this->getReceiverLink();
	}

	public function getReceiverLink(): string
	{
		return $this->getLink($this->transaction->getReceiver());
	}

	public function getSenderLink(): string
	{
		return $this->getLink($this->transaction->getSender());
	}

	/**
	 * @return string
	 */
	private function getLink(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => '/embassy/faction-' . $part->id,
			Player::class => '/embassy/player-' . $part->id,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function displayName(): string
	{
		return $this->side === 'sender' ? $this->getSenderName() : $this->getReceiverName();
	}

	public function getReceiverName(): string
	{
		return $this->getName($this->transaction->getReceiver());
	}

	public function getSenderName(): string
	{
		return $this->getName($this->transaction->getSender());
	}

	private function getName(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => ($this->getFactionsConfiguration)($part, 'popularName'),
			Player::class => $part->name,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function displayAvatar(): string
	{
		return $this->side === 'sender' ? $this->getSenderAvatar() : $this->getReceiverAvatar();
	}

	public function getReceiverAvatar(): string
	{
		return $this->getAvatar($this->transaction->getReceiver());
	}

	public function getSenderAvatar(): string
	{
		return $this->getAvatar($this->transaction->getSender());
	}

	private function getAvatar(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => 'color-' . $part->identifier,
			Player::class => $part->avatar,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function displayStatus(): string
	{
		return $this->side === 'sender' ? $this->getSenderStatus() : $this->getReceiverStatus();
	}

	public function getReceiverStatus(): string
	{
		return $this->getStatus($this->transaction->getReceiver());
	}

	public function getSenderStatus(): string
	{
		return $this->getStatus($this->transaction->getSender());
	}

	private function getStatus(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => ($this->getFactionsConfiguration)($part, 'government'),
			// TODO make a method to get a player status
			Player::class => ($this->getFactionsConfiguration)($part->faction, 'status')[$part->status - 1],
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function displayFaction(): int
	{
		return $this->side === 'sender' ? $this->getSenderColor() : $this->getReceiverColor();
	}

	public function getReceiverColor(): int
	{
		return $this->getFaction($this->transaction->getReceiver());
	}

	public function getSenderColor(): int
	{
		return $this->getFaction($this->transaction->getSender());
	}

	private function getFaction(CreditHolderInterface $part): int
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => $part->identifier,
			Player::class => $part->faction->identifier,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}
}
