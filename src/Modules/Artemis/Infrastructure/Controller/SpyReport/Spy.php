<?php

namespace App\Modules\Artemis\Infrastructure\Controller\SpyReport;

use App\Modules\Artemis\Application\Handler\NpcSpyingHandler;
use App\Modules\Artemis\Application\Handler\PlayerSpyingHandler;
use App\Modules\Artemis\Application\Handler\SpyingHandler;
use App\Modules\Artemis\Domain\Event\SpyEvent;
use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Enum\PlaceType;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Spy extends AbstractController
{
	public function __construct(
		#[TaggedLocator('app.spying.handler')]
		private readonly ServiceLocator $spyingHandlers,
	) {
	}

	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlaceRepositoryInterface $placeRepository,
		PlayerManager $playerManager,
		SpyReportRepositoryInterface $spyReportRepository,
		EventDispatcherInterface $eventDispatcher,
	): Response {
		$placeId = $request->query->get('baseId') ?? throw new BadRequestHttpException('Missing place ID');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('Place ID must be a valid UUID');
		}

		$price = $request->query->getInt('price', $request->request->getInt('price'));

		if ($price > 1000000 || 0 === $price) {
			throw new BadRequestHttpException('Impossible de lancer un espionnage avec le montant proposé');
		}

		if (!$currentPlayer->canAfford($price)) {
			throw new ConflictHttpException('Not enough credits to pay for that spying operation');
		}
		$place = $placeRepository->get(Uuid::fromString($placeId)) ?? throw $this->createNotFoundException('Place not found');

		// TODO convert into specification/Voter
		if (PlaceType::Planet !== $place->typeOfPlace || $place->base?->player->faction->id->equals($currentPlayer->faction->id)) {
			throw new ConflictHttpException('You cannot spy this place');
		}
		$spyReport = $this->getSpyingHandler($place)->spy($place, $currentPlayer, $price);

		$spyReportRepository->save($spyReport);

		$playerManager->decreaseCredit($currentPlayer, $price);

		$eventDispatcher->dispatch(new SpyEvent($spyReport, $currentPlayer));

		$this->addFlash('success', 'Espionnage effectué.');

		return $this->redirectToRoute('spy_reports', ['report' => $spyReport->id]);
	}

	public function getSpyingHandler(Place $place): SpyingHandler
	{
		return $this->spyingHandlers->get(
			null !== $place->base
				? PlayerSpyingHandler::class
				: NpcSpyingHandler::class
		);
	}
}
