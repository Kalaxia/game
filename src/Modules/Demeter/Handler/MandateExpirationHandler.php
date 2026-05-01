<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Event\MandateStartEvent;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Message\MandateExpirationMessage;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[WithMonologChannel('political_events')]
#[AsMessageHandler]
class MandateExpirationHandler
{
	public function __construct(
		private ConversationRepositoryInterface $conversationRepository,
		private MandateRepositoryInterface $mandateRepository,
		private PlayerRepositoryInterface $playerRepository,
		private EntityManagerInterface $entityManager,
		private EventDispatcherInterface $eventDispatcher,
	) {
	}

	public function __invoke(MandateExpirationMessage $message): void
	{
		$expiredMandate = $this->mandateRepository->get($message->mandateId)
			?? throw new \RuntimeException(sprintf('No mandate found for ID %s', $message->mandateId->toRfc4122()));
		$newMandate = $this->mandateRepository->getLastMandate($expiredMandate->faction)
			?? throw new \RuntimeException(sprintf('No mandate found for faction %s.', $message->mandateId->toRfc4122()));

		$governmentMembers = $this->playerRepository->getBySpecification(new IsGovernmentMember($expiredMandate->faction));

		foreach ($governmentMembers as $governmentMember) {
			$governmentMember->status = Player::PARLIAMENT;
		}

		if (null !== $newMandate->leader) {
			$newMandate->leader->status = Player::CHIEF;
		}

		$factionAccount = $this->playerRepository->getFactionAccount($newMandate->faction);

		$this->eventDispatcher->dispatch(new MandateStartEvent(
			factionConversation: $this->conversationRepository->getOneByPlayer($factionAccount),
			factionPlayer: $factionAccount,
			expiredMandate: $expiredMandate,
			newMandate: $newMandate,
		));

		$this->entityManager->flush();
	}
}
