<?php

use App\Classes\Exception\FormException;
use App\Classes\Library\Flashbag;

$request = $this->getContainer()->get('app.request');
$response = $this->getContainer()->get('app.response');
$session = $this->getContainer()->get(App\Classes\Library\Session\SessionWrapper::class);
$topicManager = $this->getContainer()->get(App\Modules\Demeter\Manager\Forum\ForumTopicManager::class);

$id = $request->query->get('id');

if (false !== $id) {
	$S_TOM = $topicManager->getCurrentSession();
	$topicManager->newSession();
	$topicManager->load(['id' => $id]);

	if (1 == $topicManager->size()) {
		if ($session->get('playerInfo')->get('status') > 2) {
			if (1 == $topicManager->get()->isClosed) {
				$topicManager->get()->isClosed = 0;
			} else {
				$topicManager->get()->isClosed = 1;
			}
			$session->addFlashbag('Le sujet a bien été fermé/ouvert', Flashbag::TYPE_SUCCESS);
		} else {
			throw new FormException('Vous n\'avez pas les droits');
		}
	} else {
		throw new FormException('Ce sujet n\'existe pas');
	}

	$topicManager->changeSession($S_TOM);
} else {
	throw new FormException('Manque d\'information');
}
