<?php

// write a message in the roadmap action

// string content 	contenu du message
// [int statement] 	état (0 = caché, 1 = affiché)

use App\Classes\Exception\FormException;
use App\Classes\Library\Flashbag;
use App\Classes\Library\Utils;
use App\Modules\Hermes\Model\RoadMap;

$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(App\Classes\Library\Session\SessionWrapper::class);
$parser = $this->getContainer()->get(App\Classes\Library\Parser::class);
$roadmapManager = $this->getContainer()->get(App\Modules\Hermes\Manager\RoadMapManager::class);

if (false == $session->get('playerInfo')->get('admin')) {
	$response->redirect('profil');
} else {
	$content = $request->request->get('content');
	$statement = $request->query->get('statement', false);

	if (!empty($content)) {
		$rm = new RoadMap();
		$rm->rPlayer = $session->get('playerId');
		$rm->setContent($content);
		$rm->setParsedContent($parser->parse($content));
		if (false !== $statement) {
			if (0 == $statement or 1 == $statement) {
				$rm->statement = $statement;
			}
		} else {
			$rm->statement = RoadMap::DISPLAYED;
		}
		$rm->dCreation = Utils::now();
		$roadmapManager->add($rm);

		$session->addFlashbag('Roadmap publiée', Flashbag::TYPE_SUCCESS);
	} else {
		throw new FormException('pas assez d\'informations pour écrire un message dans la roadmap');
	}
}
