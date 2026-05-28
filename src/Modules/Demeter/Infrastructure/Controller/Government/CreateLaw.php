<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Domain\Factory\LawFactory;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Law\GetPrice;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Message\Law\VoteMessage;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateLaw extends AbstractController
{
	public function __construct(
		#[Autowire('%politics_law_max_duration%')]
		private readonly int $lawMaxDuration,
	) {
	}

	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorRepositoryInterface $colorRepository,
		ColorManager $colorManager,
		GetPrice $getPrice,
		LawFactory $lawFactory,
		MessageBusInterface $messageBus,
		LawRepositoryInterface $lawRepository,
	): Response {
		$type = $request->query->getInt('type');
		$duration = $request->request->getInt('duration');

		// TODO replace with Voter
		if ($currentPlayer->status !== LawResources::getInfo($type, 'department')) {
			throw $this->createAccessDeniedException('Vous n\' avez pas le droit de proposer cette loi.');
		}

		if (0 !== $duration && $duration > $this->lawMaxDuration) {
			throw new BadRequestHttpException(sprintf('Maximum law duration is %d cycles', $this->lawMaxDuration));
		}
		$isRulerLaw = PlayerStatus::Chief === LawResources::getInfo($type, 'department');

		$lawPrice = $getPrice($type, $currentPlayer->faction, $duration);

		if (!$currentPlayer->faction->canAfford($lawPrice)) {
			throw new ConflictHttpException('Il n\'y a pas assez de crédits dans les caisses de l\'Etat.');
		}

		try {
			$law = $lawFactory->createFromPayload($type, $duration, $currentPlayer, $request->request->all());
		} catch (\InvalidArgumentException $e) {
			throw new BadRequestHttpException(message: $e->getMessage(), previous: $e);
		} catch (\DomainException $e) {
			throw new ConflictHttpException(message: $e->getMessage(), previous: $e);
		} catch (\UnexpectedValueException $e) {
			throw $this->createAccessDeniedException(message: $e->getMessage(), previous: $e);
		}

		$currentPlayer->faction->credits -= $lawPrice;

		$colorRepository->save($currentPlayer->faction);
		$lawRepository->save($law);

		$messageBus->dispatch(
			new VoteMessage($law->id),
			[DateTimeConverter::to_delay_stamp($law->voteEndedAt)],
		);

		$colorManager->sendSenateNotif($currentPlayer->faction, $isRulerLaw);

		return $this->redirectToRoute('faction_senate');
	}
}
