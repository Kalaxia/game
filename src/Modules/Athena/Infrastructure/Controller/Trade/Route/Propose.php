<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Propose extends AbstractController
{
    public function __invoke(
        Request $request,
        OrbitalBase $currentBase,
        Player $currentPlayer,
        CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
        ColorManager $colorManager,
        OrbitalBaseManager $orbitalBaseManager,
        OrbitalBaseHelper $orbitalBaseHelper,
        CommercialRouteManager $commercialRouteManager,
        NotificationManager $notificationManager,
        PlayerManager $playerManager,
    ): Response {
        $baseFrom = $request->query->get('sourceBase');
        $baseTo = $request->query->get('destinationBase');

        if (false !== $baseFrom and false !== $baseTo) {
            if (null === ($proposerBase = $currentPlayerBasesRegistry->get($baseFrom))) {
                throw $this->createNotFoundException('This base does not exist or does not belong to you');
            }
            $otherBase = $orbitalBaseManager->get($baseTo);

            $nbrMaxCommercialRoute = $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::SPATIOPORT, 'level', $proposerBase->getLevelSpatioport(), 'nbRoutesMax');

            // Check if a route already exists between these two bases
            $alreadyARoute = $commercialRouteManager->isAlreadyARoute($proposerBase->getId(), $otherBase->getId());

            if (($commercialRouteManager->countBaseRoutes($proposerBase->getId()) < $nbrMaxCommercialRoute) && (!$alreadyARoute) && ($proposerBase->getLevelSpatioport() > 0) && ($otherBase->getLevelSpatioport() > 0)) {
                $player = $playerManager->get($otherBase->getRPlayer());

                $playerFaction = $colorManager->get($currentPlayer->rColor);
                $otherFaction = $colorManager->get($player->rColor);

                if (Color::ENEMY !== $playerFaction->colorLink[$player->rColor] && Color::ENEMY !== $otherFaction->colorLink[$currentPlayer->rColor]) {
                    if (null !== $proposerBase && null !== $otherBase && ($proposerBase->getRPlayer() != $otherBase->getRPlayer()) && null !== $player) {
                        $distance = Game::getDistance($proposerBase->getXSystem(), $otherBase->getXSystem(), $proposerBase->getYSystem(), $otherBase->getYSystem());
                        $bonusA = ($proposerBase->getSector() !== $otherBase->getSector()) ? $this->getParameter('athena.trade.route.sector_bonus') : 1;
                        $bonusB = ($currentPlayer->rColor !== $player->getRColor()) ? $this->getParameter('athena.trade.route.color_bonus') : 1;
                        $price = Game::getRCPrice($distance);
                        $income = Game::getRCIncome($distance, $bonusA, $bonusB);

                        if (1 == $distance) {
                            $imageLink = '1-'.rand(1, 3);
                        } elseif ($distance < 26) {
                            $imageLink = '2-'.rand(1, 3);
                        } elseif ($distance < 126) {
                            $imageLink = '3-'.rand(1, 3);
                        } else {
                            $imageLink = '4-'.rand(1, 3);
                        }

                        // compute bonus
                        if (in_array(ColorResource::COMMERCIALROUTEPRICEBONUS, $playerFaction->bonus)) {
                            $priceWithBonus = round($price - ($price * ColorResource::BONUS_NEGORA_ROUTE / 100));
                        } else {
                            $priceWithBonus = $price;
                        }

                        if ($currentPlayer->credit >= $priceWithBonus) {
                            // création de la route
                            $cr = new CommercialRoute();
                            $cr->setROrbitalBase($proposerBase->getId());
                            $cr->setROrbitalBaseLinked($otherBase->getId());
                            $cr->setImageLink($imageLink);
                            $cr->setDistance($distance);
                            $cr->setPrice($price);
                            $cr->setIncome($income);
                            $cr->setDProposition(Utils::now());
                            $cr->setDCreation(null);
                            $cr->setStatement(0);
                            $commercialRouteManager->add($cr);

                            // débit des crédits au joueur
                            $playerManager->decreaseCredit($currentPlayer, $priceWithBonus);

                            $n = new Notification();
                            $n->setRPlayer($otherBase->getRPlayer());
                            $n->setTitle('Proposition de route commerciale');
                            $n->addBeg()->addLnk('embassy/player-'.$currentPlayer->id, $currentPlayer->name);
                            $n->addTxt(' vous propose une route commerciale liant ');
                            $n->addLnk('map/place-'.$proposerBase->getRPlace(), $proposerBase->getName())->addTxt(' et ');
                            $n->addLnk('map/place-'.$otherBase->getRPlace(), $otherBase->getName())->addTxt('.');
                            $n->addSep()->addTxt('Les frais de l\'opération vous coûteraient '.Format::numberFormat($priceWithBonus).' crédits; Les gains estimés pour cette route sont de '.Format::numberFormat($income).' crédits par relève.');
                            $n->addSep()->addLnk('action/a-switchbase/base-'.$otherBase->getRPlace().'/page-spatioport', 'En savoir plus ?');
                            $n->addEnd();
                            $notificationManager->add($n);

                            $this->addFlash('success', 'Route commerciale proposée');

                            return $this->redirect($request->headers->get('referer'));
                        } else {
                            throw new ErrorException('impossible de proposer une route commerciale - vous n\'avez pas assez de crédits');
                        }
                    } else {
                        throw new ErrorException('impossible de proposer une route commerciale (2)');
                    }
                } else {
                    throw new ErrorException('impossible de proposer une route commerciale à ce joueur, vos factions sont en guerre.');
                }
            } else {
                throw new ErrorException('impossible de proposer une route commerciale (3)');
            }
        } else {
            throw new FormException('pas assez d\'informations pour proposer une route commerciale');
        }
    }
}
