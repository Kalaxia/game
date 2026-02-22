<?php

use App\Classes\Exception\ErrorException;

$container = $this->getContainer();
$ajaxPath = $container->getParameter('ajax');
$pagesPath = $container->getParameter('pages');

match ($this->getContainer()->get('app.request')->query->get('a')) {
	'switchparams' => include $ajaxPath.'common/switchParams.php',
	'readnotif' => include $ajaxPath.'hermes/notification/read.php',
	'archivenotif' => include $ajaxPath.'hermes/notification/archive.php',
	'deletenotif' => include $ajaxPath.'hermes/notification/delete.php',
	'assignship' => include $ajaxPath.'ares/ship/assign.php',
	'updatesquadron' => include $ajaxPath.'ares/squadron/update.php',
	'increaseinvestuni' => include $ajaxPath.'zeus/university/increaseInvest.php',
	'decreaseinvestuni' => include $ajaxPath.'zeus/university/decreaseInvest.php',
	'autocompleteplayer' => include $ajaxPath.'autocomplete/player.php',
	'autocompleteorbitalbase' => include $ajaxPath.'autocomplete/orbitalBase.php',
	'loadsystem' => include $pagesPath.'ajax/loadSystem.php',
	'buildingpanel' => include $pagesPath.'ajax/buildingPanel.php',
	'shippanel' => include $pagesPath.'ajax/shipPanel.php',
	'technopanel' => include $pagesPath.'ajax/technoPanel.php',
	'moremessage' => include $pagesPath.'ajax/conversation/message.php',
	'moreconversation' => include $pagesPath.'ajax/conversation/conversation.php',
	'morerank' => include $pagesPath.'ajax/morerank.php',
	'wswpy' => include $pagesPath.'ajax/wsw/player.php',
	'wswpl' => include $pagesPath.'ajax/wsw/place.php',
	default => throw new ErrorException('action inconnue ou non-référencée'),
};
