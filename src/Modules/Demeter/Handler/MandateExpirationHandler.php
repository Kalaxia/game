<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Message\MandateExpirationMessage;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MandateExpirationHandler
{
	public function __construct(
		private ColorRepositoryInterface $factionRepository,
		private MandateRepositoryInterface $mandateRepository,
		private PlayerRepositoryInterface $playerRepository,
		private EntityManagerInterface $entityManager,
	) {
	}

	public function __invoke(MandateExpirationMessage $message): void
	{
		$faction = $this->factionRepository->get($message->factionId)
			?? throw new \RuntimeException(sprintf('Faction with id %s not found.', $message->factionId));
		$mandate = $this->mandateRepository->getLastMandate($faction)
			?? throw new \RuntimeException(sprintf('No mandate found for faction %s.', $faction->identifier));


		$governmentMembers = $this->playerRepository->getBySpecification(new IsGovernmentMember($faction));

		foreach ($governmentMembers as $governmentMember) {
			$governmentMember->status = Player::PARLIAMENT;
		}

		$mandate->leader->status = Player::CHIEF;

		$this->entityManager->flush();
	}
}
