<?php

namespace App\Modules\Promethee\Manager;

use App\Classes\Container\StackList;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Promethee\Model\Research;
use App\Modules\Zeus\Model\Player;

readonly class ResearchManager
{
	public function __construct(
		private ResearchRepositoryInterface     $researchRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private ResearchHelper                  $researchHelper,
		private int                             $researchQuantity
	) {
	}

	// @TODO move and refactor this code
	public function update(
		Research $research,
		Player $player,
		int $naturalInvest,
		int $lifeInvest,
		int $socialInvest,
		int $informaticInvest,
	): void {
		// $applyPrestige = ColorResource::APHERA == $player->faction->identifier;
		// natural technologies
		do {
			if ($research->naturalToPay > $naturalInvest) {
				$research->naturalToPay -= $naturalInvest;
				$naturalInvest = 0;
			} else {
				$naturalInvest -= $research->naturalToPay;
				switch ($research->naturalTech) {
					case 0:
						$research->mathLevel++;
						$levelReached = $research->mathLevel;
						break;
					case 1:
						$research->physLevel++;
						$levelReached = $research->physLevel;
						break;
					case 2:
						$research->chemLevel++;
						$levelReached = $research->chemLevel;
						break;
					default:
						$levelReached = 0;
						throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies');
				}

				$n = NotificationBuilder::new()
					->setTitle(sprintf(
						'%s niveau %d',
						$this->researchHelper->getInfo($research->naturalTech, 'name'),
						$levelReached,
					))
					->setContent(sprintf(
						<<<END
						Vos investissements dans l\'Université ont payé !<br />
						Vos chercheurs du département des <strong>Sciences Naturelles</strong> ont fait des avancées en <strong>
						%s</strong>. Vous êtes actuellement au <strong>niveau 
						%s</strong> dans ce domaine. Félicitations !
						END,
						$this->researchHelper->getInfo($research->naturalTech, 'name'),
						$levelReached,
					))
					->for($player);
				$this->notificationRepository->save($n);
				do {
					$research->naturalTech = random_int(0, 2); // 0, 1 ou 2
					$tech1 = $research->mathLevel;
					$tech2 = $research->physLevel;
					$tech3 = $research->chemLevel;
					match ($research->naturalTech) {
                        0 => $tech1++,
                        1 => $tech2++,
                        2 => $tech3++,
                        default => throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies'),
                    };
				} while (!$this->researchHelper->isResearchPermit($tech1, $tech2, $tech3));

				$research->naturalToPay = $this->researchHelper->getInfo(
					$research->naturalTech,
					'level',
					$research->getLevel($research->naturalTech) + 1,
					'price',
				);
			}
		} while ($naturalInvest > 0);
		// life technologies (en fait ce sont les sciences politiques)
		do {
			if ($research->lifeToPay > $lifeInvest) {
				$research->lifeToPay -= $lifeInvest;
				$lifeInvest = 0;
			} else {
				$lifeInvest -= $research->lifeToPay;
				switch ($research->lifeTech) {
					case 3:
						$research->bioLevel++;
						$levelReached = $research->bioLevel;
						break;
					case 4:
						$research->mediLevel++;
						$levelReached = $research->mediLevel;
						break;
					default:
						$levelReached = 0;
						throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies');
				}

				$n = NotificationBuilder::new()
					->setTitle(sprintf(
						'%s niveau %d',
						$this->researchHelper->getInfo($research->lifeTech, 'name'),
						$levelReached,
					))
					->setContent(sprintf(
						'Vos investissements dans l\'Université ont payé !<br />
						Vos chercheurs du département des <strong>Sciences Politiques</strong> ont fait des avancées en <strong>
						%s</strong>. Vous êtes actuellement au <strong>niveau
						%s</strong> dans ce domaine. Félicitations !',
						$this->researchHelper->getInfo($research->lifeTech, 'name'),
						$levelReached,
					))
					->for($player);

				$this->notificationRepository->save($n);

				do {
					$research->lifeTech = random_int(3, 4);
					$tech1 = $research->bioLevel;
					$tech2 = $research->mediLevel;
					match ($research->lifeTech) {
                        3 => $tech1++,
                        4 => $tech2++,
                        default => throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies'),
                    };
				} while (!$this->researchHelper->isResearchPermit($tech1, $tech2));
				$research->lifeToPay = $this->researchHelper->getInfo(
					$research->lifeTech,
					'level',
					$research->getLevel($research->lifeTech) + 1,
					'price',
				);
			}
		} while ($lifeInvest > 0);
		// social technologies
		do {
			if ($research->socialToPay > $socialInvest) {
				$research->socialToPay -= $socialInvest;
				$socialInvest = 0;
			} else {
				$socialInvest -= $research->socialToPay;
				switch ($research->socialTech) {
					case 5:
						$research->econoLevel++;
						$levelReached = $research->econoLevel;
						break;
					case 6:
						$research->psychoLevel++;
						$levelReached = $research->psychoLevel;
						break;
					default:
						$levelReached = 0;
						throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies');
				}

				$n = NotificationBuilder::new()
					->setTitle(sprintf(
						'%s niveau %d',
						$this->researchHelper->getInfo($research->socialTech, 'name'),
						$levelReached,
					))
					->setContent(sprintf(
						'Vos investissements dans l\'Université ont payé !<br />
						Vos chercheurs du département des <strong>Sciences Economiques et Sociales</strong> ont fait des avancées en <strong>
						%s</strong>. Vous êtes actuellement au <strong>niveau 
						%d</strong> dans ce domaine. Félicitations !',
						$this->researchHelper->getInfo($research->socialTech, 'name'),
						$levelReached,
					))
					->for($player);
				$this->notificationRepository->save($n);
				do {
					$research->socialTech = random_int(5, 6);
					$tech1 = $research->econoLevel;
					$tech2 = $research->psychoLevel;
					match ($research->socialTech) {
                        5 => $tech1++,
                        6 => $tech2++,
                        default => throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies'),
                    };
				} while (!$this->researchHelper->isResearchPermit($tech1, $tech2));
				$research->socialToPay = $this->researchHelper->getInfo(
					$research->socialTech,
					'level',
					$research->getLevel($research->socialTech) + 1,
					'price'
				);
			}
		} while ($socialInvest > 0);
		// informatic technologies
		do {
			if ($research->informaticToPay > $informaticInvest) {
				$research->informaticToPay -= $informaticInvest;
				$informaticInvest = 0;
			} else {
				$informaticInvest -= $research->informaticToPay;
				switch ($research->informaticTech) {
					case 7:
						$research->networkLevel++;
						$levelReached = $research->networkLevel;
						break;
					case 8:
						$research->algoLevel++;
						$levelReached = $research->algoLevel;
						break;
					case 9:
						$research->statLevel++;
						$levelReached = $research->statLevel;
						break;
					default:
						$levelReached = 0;
						throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies');
				}

				$n = NotificationBuilder::new()
					->setTitle(sprintf(
						'%s niveau %d',
						$this->researchHelper->getInfo($research->informaticTech, 'name'),
						$levelReached,
					))
					->setContent(sprintf(
						'Vos investissements dans l\'Université ont payé !<br />
						Vos chercheurs du département de l\'<strong>Ingénierie Informatique</strong> ont fait des avancées en <strong>
						%s</strong>. Vous êtes actuellement au <strong>niveau 
						%d</strong> dans ce domaine. Félicitations !',
						$this->researchHelper->getInfo($research->informaticTech, 'name'),
						$levelReached,
					))
					->for($player);

				$this->notificationRepository->save($n);

				do {
					$research->informaticTech = random_int(7, 9);
					$tech1 = $research->networkLevel;
					$tech2 = $research->algoLevel;
					$tech3 = $research->statLevel;
					match ($research->informaticTech) {
                        7 => $tech1++,
                        8 => $tech2++,
                        9 => $tech3++,
                        default => throw new \LogicException('une erreur est survenue lors de la mise à jour des technologies'),
                    };
				} while (!$this->researchHelper->isResearchPermit($tech1, $tech2, $tech3));
				$research->informaticToPay = $this->researchHelper->getInfo(
					$research->informaticTech,
					'level',
					$research->getLevel($research->informaticTech) + 1,
					'price',
				);
			}
		} while ($informaticInvest > 0);

		$this->researchRepository->save($research);
	}

	public function getResearchList(Research $research): StackList
	{
		// return a stacklist of the researches
		$r = new StackList();
		for ($i = 0; $i < $this->researchQuantity; ++$i) {
			$r->append($research->getLevel($i));
		}

		return $r;
	}
}
