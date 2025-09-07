<?php

use App\Classes\Exception\ErrorException;

$container = $this->getContainer();
$request = $this->getContainer()->get('app.request');
$response = $this->getContainer()->get('app.response');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$actionPath = $container->getParameter('action');

// démarre la redirection standard vers la page précédente
$response->redirect($session->getLastHistory());

if ($request->query->has('sftr')) {
	$session->add('sftr', $request->query->get('sftr'));
}

if ($request->query->has('token') && $session->get('token') === $request->query->get('token')) {
	match ($request->query->get('a')) {
        'switchplanet' => include $actionPath.'common/switchBase.php',
        'switchparams' => include $actionPath.'common/switchParams.php',
        'sendsponsorshipemail' => include $actionPath.'common/sendSponsorshipEmail.php',
        'discordrequest' => include $actionPath.'common/discordRequest.php',
        'updateinvest' => include $actionPath.'athena/general/updateInvest.php',
        'switchdockmode' => include $actionPath.'athena/general/switchDockMode.php',
        'createschoolclass' => include $actionPath.'athena/general/createSchoolClass.php',
        'giveresource' => include $actionPath.'athena/general/giveResource.php',
        'giveships' => include $actionPath.'athena/general/giveShips.php',
        'renamebase' => include $actionPath.'athena/general/renameBase.php',
        'changebasetype' => include $actionPath.'athena/general/changeBaseType.php',
        'leavebase' => include $actionPath.'athena/general/leaveBase.php',
        'buildbuilding' => include $actionPath.'athena/building/build.php',
        'dequeuebuilding' => include $actionPath.'athena/building/dequeue.php',
        'buildship' => include $actionPath.'athena/ship/build.php',
        'dequeueship' => include $actionPath.'athena/ship/dequeue.php',
        'recycleship' => include $actionPath.'athena/ship/recycle.php',
        'proposeroute' => include $actionPath.'athena/route/propose.php',
        'acceptroute' => include $actionPath.'athena/route/accept.php',
        'refuseroute' => include $actionPath.'athena/route/refuse.php',
        'cancelroute' => include $actionPath.'athena/route/cancel.php',
        'deleteroute' => include $actionPath.'athena/route/delete.php',
        'proposetransaction' => include $actionPath.'athena/transaction/propose.php',
        'accepttransaction' => include $actionPath.'athena/transaction/accept.php',
        'canceltransaction' => include $actionPath.'athena/transaction/cancel.php',
        'createmission' => include $actionPath.'athena/recycling/createMission.php',
        'cancelmission' => include $actionPath.'athena/recycling/cancelMission.php',
        'addtomission' => include $actionPath.'athena/recycling/addToMission.php',
        'startconversation' => include $actionPath.'hermes/conversation/start.php',
        'writeconversation' => include $actionPath.'hermes/conversation/write.php',
        'leaveconversation' => include $actionPath.'hermes/conversation/leave.php',
        'adduserconversation' => include $actionPath.'hermes/conversation/addUser.php',
        'updatedisplayconversation' => include $actionPath.'hermes/conversation/updateDisplay.php',
        'updatetitleconversation' => include $actionPath.'hermes/conversation/updateTitle.php',
        'writeofficialconversation' => include $actionPath.'hermes/conversation/writeOfficial.php',
        'writefactionconversation' => include $actionPath.'hermes/conversation/writeFaction.php',
        'readallnotif' => include $actionPath.'hermes/notification/readAll.php',
        'deleteallnotif' => include $actionPath.'hermes/notification/deleteAll.php',
        'deletenotif' => include $actionPath.'hermes/notification/delete.php',
        'archivenotif' => include $actionPath.'hermes/notification/archive.php',
        'writeroadmap' => include $actionPath.'hermes/roadmap/write.php',
        'buildtechno' => include $actionPath.'promethee/technology/build.php',
        'dequeuetechno' => include $actionPath.'promethee/technology/dequeue.php',
        'searchplayer' => include $actionPath.'zeus/player/searchPlayer.php',
        'updateuniinvest' => include $actionPath.'zeus/player/updateUniInvest.php',
        'disconnect' => include $actionPath.'zeus/player/disconnect.php',
        'sendcredit' => include $actionPath.'zeus/player/sendCredit.php',
        'sendcredittofaction' => include $actionPath.'zeus/player/sendCreditToFaction.php',
        'sendcreditfromfaction' => include $actionPath.'zeus/player/sendCreditFromFaction.php',
        'abandonserver' => include $actionPath.'zeus/player/abandonServer.php',
        'switchadvertisement' => include $actionPath.'zeus/player/switchAdvertisement.php',
        'validatestep' => include $actionPath.'zeus/tutorial/validateStep.php',
        'spy' => include $actionPath.'artemis/spy.php',
        'deletespyreport' => include $actionPath.'artemis/delete.php',
        'deleteallspyreport' => include $actionPath.'artemis/deleteAll.php',
        'archivereport' => include $actionPath.'ares/report/archive.php',
        'deletereport' => include $actionPath.'ares/report/delete.php',
        'deleteallreport' => include $actionPath.'ares/report/deleteAll.php',
        'movefleet' => include $actionPath.'ares/fleet/move.php',
        'loot' => include $actionPath.'ares/fleet/loot.php',
        'colonize' => include $actionPath.'ares/fleet/colonize.php',
        'conquer' => include $actionPath.'ares/fleet/conquer.php',
        'cancelmove' => include $actionPath.'ares/fleet/cancel.php',
        'affectcommander' => include $actionPath.'ares/commander/affect.php',
        'putcommanderinschool' => include $actionPath.'ares/commander/putInSchool.php',
        'updatenamecommander' => include $actionPath.'ares/commander/updateName.php',
        'emptycommander' => include $actionPath.'ares/commander/empty.php',
        'firecommander' => include $actionPath.'ares/commander/fire.php',
        'changeline' => include $actionPath.'ares/commander/changeLine.php',
        'writemessageforum' => include $actionPath.'demeter/message/write.php',
        'movetopicforum' => include $actionPath.'demeter/topic/move.php',
        'closetopicforum' => include $actionPath.'demeter/topic/close.php',
        'uptopicforum' => include $actionPath.'demeter/topic/up.php',
        'archivetopicforum' => include $actionPath.'demeter/topic/archive.php',
        'editmessageforum' => include $actionPath.'demeter/message/edit.php',
        'createtopicforum' => include $actionPath.'demeter/topic/createTopic.php',
        'writenews' => include $actionPath.'demeter/news/write.php',
        'editnews' => include $actionPath.'demeter/news/edit.php',
        'pinnews' => include $actionPath.'demeter/news/pin.php',
        'deletenews' => include $actionPath.'demeter/news/delete.php',
        'postulate' => include $actionPath.'demeter/election/postulate.php',
        'makeacoup' => include $actionPath.'demeter/election/makeACoup.php',
        'vote' => include $actionPath.'demeter/election/vote.php',
        'choosegovernment' => include $actionPath.'demeter/election/chooseGovernment.php',
        'fireminister' => include $actionPath.'demeter/election/fire.php',
        'resign' => include $actionPath.'demeter/election/resign.php',
        'abdicate' => include $actionPath.'demeter/election/abdicate.php',
        'votelaw' => include $actionPath.'demeter/law/vote.php',
        'createlaw' => include $actionPath.'demeter/law/createLaw.php',
        'updatefactiondesc' => include $actionPath.'demeter/updateFactionDesc.php',
        'donate' => include $actionPath.'demeter/donate.php',
        default => throw new ErrorException('action inconnue ou non-référencée'),
    };
} elseif ('switchplanet' == $request->query->get('a')) {
	// action sans token
	include $actionPath.'common/switchBase.php';
} else {
	throw new ErrorException('votre token CSRF a expiré');
}
