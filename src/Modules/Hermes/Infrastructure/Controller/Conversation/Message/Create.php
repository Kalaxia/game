<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Controller\Conversation\Message;

use App\Classes\Library\Parser;
use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

class Create extends AbstractController
{
	#[Route(
		path: '/conversations/{conversationId}/messages',
		name: 'new_conversation_message',
		requirements: [
			'conversationId' => Requirement::UUID_V4,
		],
		methods: Request::METHOD_POST,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ConversationRepositoryInterface $conversationRepository,
		ConversationMessageRepositoryInterface $conversationMessageRepository,
		Parser $parser,
		Uuid $conversationId,
	): Response {
		$conversation = $conversationRepository->getOne($conversationId)
			?? throw $this->createNotFoundException('Conversation not found');
		$content = $request->request->get('content')
			?? throw new BadRequestHttpException('Missing content parameter');

		if (!$conversation->hasPlayer($currentPlayer)) {
			throw $this->createAccessDeniedException('This conversation does not include you');
		}

		$content = $parser->parse($content);
		if (strlen($content) >= 10000) {
			throw new UnprocessableEntityHttpException('Le message est trop long.');
		}

		if (Conversation::TY_SYSTEM !== $conversation->type) {
			$conversation->lastMessageAt = new \DateTimeImmutable();

			// désarchiver tout les users
			foreach ($conversation->players as $conversationUser) {
				$conversationUser->conversationStatus = ConversationUser::CS_DISPLAY;

				if ($conversationUser->player->id === $currentPlayer->id) {
					$conversationUser->lastViewedAt = new \DateTimeImmutable();
				}
			}

			++$conversation->messagesCount;
			// création du message
			$message = new ConversationMessage(
				id: Uuid::v4(),
				conversation: $conversation,
				player: $currentPlayer,
				content: $content,
				createdAt: new \DateTimeImmutable(),
				type: ConversationMessage::TY_STD,
			);

			$conversationMessageRepository->save($message);
			$conversationRepository->save($conversation);
		}

		return $this->redirectToRoute('communication_center', [
			'conversationId' => $conversation->id,
		]);
	}
}
