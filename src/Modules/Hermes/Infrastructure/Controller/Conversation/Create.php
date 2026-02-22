<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Controller\Conversation;

use App\Classes\Library\Parser;
use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class Create extends AbstractController
{
	#[Route(
		path: '/messages',
		name: 'create_conversation',
		methods: Request::METHOD_POST,
	)]
	public function __invoke(
		Request $request,
		Parser $parser,
		Player $currentPlayer,
		PlayerRepositoryInterface $playerRepository,
		ConversationMessageRepositoryInterface $conversationMessageRepository,
		EntityManagerInterface $entityManager,
	): Response {
		// TODO Fix the old system with autocomplete and IDs
		$recipients = $request->request->get('name');
		$content = $request->request->get('content');

		$content = $parser->parse($content);

		if (empty($recipients) || empty($content)) {
			throw new BadRequestHttpException('Informations manquantes pour démarrer une nouvelle conversation.');
		}

		if (strlen($content) > 10000) {
			throw new BadRequestHttpException('Le message est trop long.');
		}
		// traitement des utilisateurs multiples
		$recipients = explode(',', $recipients);
		$plId = $currentPlayer->id;
		$recipients = array_filter($recipients, fn ($recipientId) => $recipientId !== $plId);

		if (count($recipients) > ConversationUser::MAX_USERS) {
			throw new BadRequestHttpException('Nombre maximum de joueur atteint.');
		}
		// chargement des utilisateurs
		// $players = $playerRepository->getByIdsAndStatements($recipients, [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]);
		$players = $playerRepository->getByNamesAndStatements($recipients, [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]);

		if (count($players) < 1) {
			throw new ConflictHttpException('Le joueur n\'est pas joignable.');
		}

		// créer la conversation
		$conv = new Conversation(
			id: Uuid::v4(),
			type: Conversation::TY_USER,
			messagesCount: 1,
			createdAt: new \DateTimeImmutable(),
			lastMessageAt: new \DateTimeImmutable(),
		);

		$entityManager->persist($conv);

		// créer le user créateur de la conversation
		$user = new ConversationUser(
			id: Uuid::v4(),
			conversation: $conv,
			player: $currentPlayer,
			lastViewedAt: new \DateTimeImmutable(),
			playerStatus: ConversationUser::US_ADMIN,
		);

		$entityManager->persist($user);

		// créer la liste des users
		foreach ($players as $player) {
			$user = new ConversationUser(
				id: Uuid::v4(),
				conversation: $conv,
				player: $player,
				lastViewedAt: null,
			);

			$entityManager->persist($user);
		}

		// créer le premier message
		$message = new ConversationMessage(
			id: Uuid::v4(),
			conversation: $conv,
			player: $currentPlayer,
			type: ConversationMessage::TY_STD,
			content: $content,
			createdAt: new \DateTimeImmutable(),
			updatedAt: new \DateTimeImmutable(),
		);

		$entityManager->persist($message);
		$entityManager->flush();

		$this->addFlash('success', 'La conversation a été créée.');

		return $this->redirectToRoute('communication_center', [
			'conversationId' => $conv->id,
		]);
	}
}
