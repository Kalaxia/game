<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\EventSubscriber;

use App\Modules\Demeter\Domain\Event\NewLeaderEvent;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class GovernmentEventSubscriber
{
	public function __construct(
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	#[AsEventListener(NewLeaderEvent::class)]
	public function onNewLeader(NewLeaderEvent $event): void
	{
		$governmentMembers = $this->playerRepository->getBySpecification(new IsGovernmentMember($event->faction));

		foreach ($governmentMembers as $governmentMember) {
			$governmentMember->status = Player::PARLIAMENT;
		}
	}
}
