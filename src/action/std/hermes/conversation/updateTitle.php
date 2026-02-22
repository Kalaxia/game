<?php

use App\Classes\Exception\ErrorException;
use App\Modules\Hermes\Model\ConversationUser;

$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(App\Classes\Library\Session\SessionWrapper::class);
$parser = $this->getContainer()->get(App\Classes\Library\Parser::class);
$conversationManager = $this->getContainer()->get(App\Modules\Hermes\Manager\ConversationManager::class);

$conversation = $request->query->get('conversation');
$title = $request->request->get('title');

$title = $parser->parse($title);

if (false !== $conversation) {
	$S_CVM = $conversationManager->getCurrentSession();
	$conversationManager->newSession();
	$conversationManager->load(
		[
			'c.id' => $conversation,
			'cu.rPlayer' => $session->get('playerId'),
			'cu.playerStatement' => ConversationUser::US_ADMIN,
		]
	);

	if (1 == $conversationManager->size()) {
		if (strlen((string) $title) < 255) {
			$conv = $conversationManager->get()->title = $title;
		} else {
			throw new ErrorException('Le titre est trop long.');
		}
	} else {
		throw new ErrorException('La conversation n\'existe pas ou ne vous appartient pas.');
	}

	$conversationManager->changeSession($S_CVM);
} else {
	throw new ErrorException('Informations manquantes pour ajouter un joueur à la conversation.');
}
