<?php

namespace App\Modules\Atlas\Manager;

use App\Classes\Library\Utils;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;

class RankingManager
{
	public function __construct(
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly string $pointsToWin,
		private readonly int $jeanMiId,
	) {
	}

	public function processWinningFaction($factionId)
	{
		$faction = $this->colorRepository->get($factionId);
		$faction->isWinner = Color::WIN;

		// @TODO refactor below
		return;

		// envoyer un message de Jean-Mi
		$winnerName = ColorResource::getInfo($faction->id, 'officialName');
		$content = 'Salut,<br /><br />La victoire vient d\'être remportée par : <br /><strong>'.$winnerName.'</strong><br />';
		$content .= 'Cette faction a atteint les '.$this->pointsToWin.' points, la partie est donc terminée.<br /><br />Bravo et un grand merci à tous les participants !';

		$S_CVM1 = $this->conversationManager->getCurrentSession();
		$this->conversationManager->newSession();
		$this->conversationManager->load(
			['cu.rPlayer' => $this->jeanMiId]
		);

		if (1 == $this->conversationManager->size()) {
			$conv = $this->conversationManager->get();

			++$conv->messages;
			$conv->dLastMessage = Utils::now();

			// désarchiver tous les users
			$users = $conv->players;
			foreach ($users as $user) {
				$user->convStatement = ConversationUser::CS_DISPLAY;
			}

			// création du message
			$message = new ConversationMessage();

			$message->rConversation = $conv->id;
			$message->rPlayer = $this->jeanMiId;
			$message->type = ConversationMessage::TY_STD;
			$message->content = $content;
			$message->dCreation = Utils::now();
			$message->dLastModification = null;

			$this->conversationMessageManager->add($message);
		} else {
			throw new ErrorException('La conversation n\'existe pas ou ne vous appartient pas.');
		}
		$this->conversationManager->changeSession($S_CVM1);
	}

	public function createRanking(bool $isPlayer, bool $isFaction): Ranking
	{
		$ranking =
			(new Ranking())
			->setIsPlayer($isPlayer)
			->setIsFaction($isFaction)
			->setCreatedAt(Utils::now())
		;
		$this->entityManager->persist($ranking);
		$this->entityManager->flush($ranking);

		return $ranking;
	}
}
