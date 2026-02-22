<?php

// écrire un message dans un topic du forum de faction
// content
// rtopic

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Flashbag;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Zeus\Resource\TutorialResource;

$request = $this->getContainer()->get('app.request');
$response = $this->getContainer()->get('app.response');
$session = $this->getContainer()->get(App\Classes\Library\Session\SessionWrapper::class);
$database = $this->getContainer()->get(App\Classes\Database\Database::class);
$topicManager = $this->getContainer()->get(App\Modules\Demeter\Manager\Forum\ForumTopicManager::class);
$forumMessageManager = $this->getContainer()->get(App\Modules\Demeter\Manager\Forum\ForumMessageManager::class);
$tutorialHelper = $this->getContainer()->get(App\Modules\Zeus\Helper\TutorialHelper::class);

$content = $request->request->get('content');
$rTopic = $request->query->get('rtopic');

if ($rTopic and $content) {
	$S_TOM_1 = $topicManager->getCurrentSession();
	$topicManager->load(['id' => $rTopic]);

	if (1 == $topicManager->size()) {
		if (!$topicManager->get()->isClosed) {
			$message = new ForumMessage();
			$message->rPlayer = $session->get('playerId');
			$message->rTopic = $rTopic;
			$message->dCreation = Utils::now();
			$message->dLastMessage = Utils::now();

			$forumMessageManager->edit($message, $content);

			$forumMessageManager->add($message);

			$topicManager->get()->dLastMessage = Utils::now();

			// tutorial
			if (false == $session->get('playerInfo')->get('stepDone')
				&& TutorialResource::FACTION_FORUM === $session->get('playerInfo')->get('stepTutorial')) {
				$tutorialHelper->setStepDone();
			}

			if (30 != $topicManager->get()->rForum) {
				$response->redirect('faction/view-forum/forum-'.$topicManager->get()->rForum.'/topic-'.$rTopic.'/sftr-2');
			}

			if (true === $this->getContainer()->getParameter('data_analysis')) {
				$qr = $database->prepare(
					'INSERT INTO 
					DA_SocialRelation(`from`, type, message, dAction)
					VALUES(?, ?, ?, ?)'
				);
				$qr->execute([$session->get('playerId'), 1, $content, Utils::now()]);
			}

			$session->addFlashbag('Message créé.', Flashbag::TYPE_SUCCESS);
		} else {
			throw new ErrorException('Ce sujet est fermé.');
		}
	} else {
		throw new ErrorException('Le topic n\'existe pas.');
	}

	$topicManager->changeSession($S_TOM_1);
} else {
	throw new FormException('Manque d\'information.');
}
