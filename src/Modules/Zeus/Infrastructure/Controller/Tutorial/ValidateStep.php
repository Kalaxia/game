<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller\Tutorial;

use App\Classes\Library\Format;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateStep extends AbstractController
{
	public function __invoke(
		Request                      $request,
		Player                       $currentPlayer,
		Planet                       $currentPlanet,
		PlanetManager                $planetManager,
		PlanetRepositoryInterface    $planetRepository,
		PlayerManager                $playerManager,
		TutorialHelper               $tutorialHelper,
		ShipQueueManager             $shipQueueManager,
		ShipQueueRepositoryInterface $shipQueueRepository,
		PlayerRepositoryInterface    $playerRepository,
		TranslatorInterface          $translator,
	): Response {
		$stepTutorial = $currentPlayer->stepTutorial;
		$session = $request->getSession();

		if (true === $currentPlayer->stepDone and TutorialResource::stepExists($stepTutorial)) {
			$experience = TutorialResource::getInfo($stepTutorial, 'experienceReward');
			$credit = TutorialResource::getInfo($stepTutorial, 'creditReward');
			$resource = TutorialResource::getInfo($stepTutorial, 'resourceReward');
			$ship = TutorialResource::getInfo($stepTutorial, 'shipReward');
			$playerBases = $planetRepository->getPlayerPlanets($currentPlayer);
			$alert = 'Etape validée. ';

			$firstReward = true;
			if ($experience > 0) {
				$firstReward = false;
				$alert .= 'Vous gagnez '.$experience.' points d\'expérience';
				$playerManager->increaseExperience($currentPlayer, $experience);
			}

			if ($credit > 0) {
				if ($firstReward) {
					$firstReward = false;
					$alert .= 'Vous gagnez '.$credit.'crédits';
				} else {
					$alert .= ', ainsi que '.$credit.' crédits';
				}
				$playerManager->increaseCredit($currentPlayer, $credit);
			}

			if ($resource > 0 || $ship != [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]) {
				$ob = $currentPlanet;

				if ($resource > 0) {
					if ($firstReward) {
						$firstReward = false;
						$alert .= 'Vous gagnez '.$resource.' ressources';
					} else {
						$alert .= ' et '.$resource.' ressources';
					}
					$alert .= ' sur votre base orbitale '.$ob->name.'. ';
				}

				if ($ship != [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]) {
					$qty = 0;
					$ships = [];
					foreach ($ship as $key => $value) {
						if (0 != $value) {
							$ships[$qty] = [];
							$ships[$qty]['quantity'] = $value;
							$ships[$qty]['name'] = $translator->trans('ship_categories.' . $key . '.name');
							++$qty;

							// add ship to dock
							$ob->addShips($key, $value);
						}
					}
					if ($firstReward) {
						$firstReward = false;
						$alert .= 'Vous gagnez ';
						$endOfAlert = ' sur votre base orbitale '.$ob->name.'. ';
					} else {
						$alert .= '. Vous gagnez également ';
						$endOfAlert = '. ';
					}

					// complete alert
					foreach ($ships as $key => $value) {
						if (0 == $key) {
							$alert .= $value['quantity'].' '.$value['name'].Format::plural($value['quantity']);
						} elseif ($qty - 1 == $key) {
							$alert .= ' et '.$value['quantity'].' '.$value['name'].Format::plural($value['quantity']);
						} else {
							$alert .= ', '.$value['quantity'].' '.$value['name'].Format::plural($value['quantity']);
						}
					}
					$alert .= $endOfAlert;
				}
			} else {
				$alert .= '. ';
			}

			$alert .= 'La prochaine étape vous attend.';
			$this->addFlash('success', $alert);

			$nextStep = $stepTutorial + 1;
			if (TutorialResource::isLastStep($stepTutorial)) {
				$this->addFlash('success', 'Bravo, vous avez terminé le tutoriel. Bonne continuation et bon amusement sur Asylamba, vous pouvez maintenant voler de vos propres ailes !');
			}

			// verify if the next step is already done
			$nextStepAlreadyDone = false;
			$redirectWithoutJeanMi = false;
			switch ($nextStep) {
				case TutorialResource::NAVIGATION:
					$redirectWithoutJeanMi = true;
					$nextStepAlreadyDone = true;
					break;
					break;
				case TutorialResource::MODIFY_UNI_INVEST:
					// asdf
					break;
				case TutorialResource::CREATE_COMMANDER:
					// asdf
					break;
					break;
				case TutorialResource::SHIP0_UNBLOCK:
					$nextStepAlreadyDone = $tutorialHelper->isNextTechnoStepAlreadyDone($currentPlayer, TechnologyId::SHIP0_UNBLOCK);
					break;
				case TutorialResource::BUILD_SHIP0:
					// verify in the queue
					// load the queues
					foreach ($playerBases as $ob) {
						$shipQueues = $shipQueueRepository->getBaseQueues($ob);
						foreach ($shipQueues as $shipQueue) {
							if (ShipCategory::LightFighter->value === $shipQueue->shipNumber) {
								$nextStepAlreadyDone = true;
								break;
							}
						}
					}
					break;
				case TutorialResource::AFFECT_COMMANDER:
					// asdf
					break;
				case TutorialResource::FILL_SQUADRON:
					// asdf
					break;
				case TutorialResource::MOVE_FLEET_LINE:
					// asdf
					break;
				case TutorialResource::SPY_PLANET:
					// asdf
					break;
				case TutorialResource::LOOT_PLANET:
					// asdf
					break;
				case TutorialResource::FACTION_FORUM:
					// asdf
					break;
				case TutorialResource::SHARE_ASYLAMBA:
					$nextStepAlreadyDone = true;
					break;
				case TutorialResource::BUILD_SHIP1:
					// asdf
					break;
				case TutorialResource::SPONSORSHIP:
					$nextStepAlreadyDone = true;
					break;
			}
			if (!$nextStepAlreadyDone) {
				$currentPlayer->stepDone = false;
				$session->get('playerInfo')->add('stepDone', false);
			}
			$currentPlayer->stepTutorial = $nextStep;
			$session->get('playerInfo')->add('stepTutorial', $nextStep);
			$playerRepository->save($currentPlayer);
			if ($redirectWithoutJeanMi) {
				return $this->redirectToRoute('profile');
			}

			return $this->redirect($request->headers->get('referer'));
		} else {
			throw new BadRequestHttpException('Impossible de valider l\'étape avant de l\'avoir effectuée.');
		}
	}
}
