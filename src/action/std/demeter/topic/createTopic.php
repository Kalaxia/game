<?php

// créer un nouveau topic
// title
// content
// rforum

use App\Classes\Exception\FormException;
use App\Classes\Library\Flashbag;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Zeus\Resource\TutorialResource;

$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(App\Classes\Library\Session\SessionWrapper::class);
$database = $this->getContainer()->get(App\Classes\Database\Database::class);
$tutorialHelper = $this->getContainer()->get(App\Modules\Zeus\Helper\TutorialHelper::class);
$topicManager = $this->getContainer()->get(App\Modules\Demeter\Manager\Forum\ForumTopicManager::class);
$forumMessageManager = $this->getContainer()->get(App\Modules\Demeter\Manager\Forum\ForumMessageManager::class);

$title = $request->request->get('title');
$content = $request->request->get('content');
$rForum = $request->query->get('rforum');

if (false !== $title and false !== $content and false !== $rForum) {
	$topic = new ForumTopic();
	$topic->title = $title;
	$topic->rForum = $rForum;
	$topic->rPlayer = $session->get('playerId');
	$topic->rColor = $session->get('playerInfo')->get('color');
	$topic->dCreation = Utils::now();
	$topic->dLastMessage = Utils::now();

	$rTopic = $topicManager->add($topic);

	$message = new ForumMessage();
	$message->rPlayer = $session->get('playerId');
	$message->rTopic = $rTopic;
	$forumMessageManager->edit($message, $content);
	$message->dCreation = Utils::now();
	$message->dLastMessage = Utils::now();

	$forumMessageManager->add($message);

	// tutorial
	if (false == $session->get('playerInfo')->get('stepDone')
		&& TutorialResource::FACTION_FORUM === $session->get('playerInfo')->get('stepTutorial')) {
		$tutorialHelper->setStepDone();
	}

	if (true === $this->getContainer()->getParameter('data_analysis')) {
		$qr = $database->prepare(
			'INSERT INTO 
			DA_SocialRelation(`from`, type, message, dAction)
			VALUES(?, ?, ?, ?)'
		);
		$qr->execute([$session->get('playerId'), 1, $content, Utils::now()]);
	}

	$response->redirect('faction/view-forum/forum-'.$topic->rForum.'/topic-'.$topic->id.'/sftr-2');
	$session->addFlashbag('Topic créé.', Flashbag::TYPE_SUCCESS);
} else {
	throw new FormException('Manque d\information.');
}
