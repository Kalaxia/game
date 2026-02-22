<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Library\Parser;
use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class WriteToFaction extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerRepositoryInterface $playerRepository,
		ConversationRepositoryInterface $conversationRepository,
		ConversationMessageRepositoryInterface $conversationMessageRepository,
		EntityManagerInterface $entityManager,
		Parser $parser,
	): Response {
		$message = $request->request->get('message') ?? throw new BadRequestHttpException('Missing message body');
		// @TODO fix empty check
		$content = $parser->parse($message);

		// @TODO replace with voter
		if (!$currentPlayer->isGovernmentMember()) {
			throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour poster un message officiel');
		}

		// TODO Replace with validator component
		if ('' === $content || strlen($content) > 25000) {
			throw new BadRequestHttpException('Le message est vide ou trop long');
		}

		$factionAccount = $playerRepository->getFactionAccount($currentPlayer->faction)
			?? throw $this->createNotFoundException('Faction account not found');

		$conversation = $conversationRepository->getOneByPlayer($factionAccount)
			?? throw $this->createNotFoundException('Faction conversation not found');

		++$conversation->messagesCount;
		$conversation->lastMessageAt = new \DateTimeImmutable();

		// désarchiver tout les users
		foreach ($conversation->players as $user) {
			$user->conversationStatus = ConversationUser::CS_DISPLAY;
		}

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
		$entityManager->flush();

		return $this->redirect($request->headers->get('referer'));
	}
}
