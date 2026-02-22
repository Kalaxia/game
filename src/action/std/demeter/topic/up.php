<?php

use App\Classes\Exception\ErrorException;
use App\Modules\Zeus\Model\Player;

$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(App\Classes\Library\Session\SessionWrapper::class);
$topicManager = $this->getContainer()->get(App\Modules\Demeter\Manager\Forum\ForumTopicManager::class);

$id = $request->query->get('id');

if (false !== $id) {
	$_TOM = $topicManager->getCurrentsession();
	$topicManager->newSession();
	$topicManager->load(['id' => $id]);

	if (1 == $topicManager->size()) {
		if (in_array($session->get('playerInfo')->get('status'), [Player::CHIEF, Player::WARLORD, Player::TREASURER, Player::MINISTER])) {
			$topic = $topicManager->get();

			if ($topic->isUp) {
				$topic->isUp = false;
			} else {
				$topic->isUp = true;
			}
		} else {
			throw new ErrorException('Vous ne disposez pas des droits nécessaires pour cette action.');
		}
	} else {
		throw new ErrorException('Le sujet demandé n\'existe pas.');
	}

	$this->getContainer()->get('app.response')->redirect('faction/view-forum/forum-'.$topicManager->get()->rForum.'/topic-'.$topicManager->get()->id.'/sftr-2');
	$topicManager->changeSession($_TOM);
} else {
	throw new ErrorException('Manque d\'information.');
}
