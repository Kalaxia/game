<?php

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Promethee\Model\Research;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class UniversityInvestmentHandler
{
	public function __construct(
		private NotificationRepositoryInterface $notificationRepository,
		private BonusApplierInterface $bonusApplier,
		private UrlGeneratorInterface $urlGenerator,
		private ResearchManager $researchManager,
		private ResearchRepositoryInterface $researchRepository,
	) {
	}

	/**
	 * @param list<Planet> $playerBases
	 */
	public function spend(PlayerFinancialReport $playerFinancialReport, array $playerBases): void
	{
		$player = $playerFinancialReport->player;
		$playerCredits = $playerFinancialReport->getNewWallet();
		$uniInvests = $player->iUniversity;

		if ($playerCredits >= $uniInvests) {
			$playerFinancialReport->universityInvestments = $uniInvests;

			$this->spendUniversityInvestments($player);

			return;
		}
		$notificationBuilder = NotificationBuilder::new()
			->setTitle('Caisses vides')
			->setContent(
				NotificationBuilder::paragraph(
					'Domaine %s',
					NotificationBuilder::divider(),
					'Vous ne disposez pas d\'assez de crédits.',
				),
				'Les revenus que vous percevez ne suffisent plus à payer vos investissements.',
			);

		if ($playerCredits > 0) {
			$newIUniversity = $playerCredits;

			$player->iUniversity = $newIUniversity;

			$playerFinancialReport->universityInvestments += $newIUniversity;

			$notificationBuilder->addContent(
				' Vos investissements dans l\'université ont été modifiés
				afin qu\'aux prochaines relèves vous puissiez payer.
				Attention, cette situation ne vous apporte pas de crédits.',
			);
		} else {
			$player->iUniversity = 0;

			$notificationBuilder->addContent(
				' Vos investissements dans l\'université ont été mis à zéro afin d\'éviter la banqueroute.'
			);
		}

		$notificationBuilder->addContent(
			NotificationBuilder::divider(),
			NotificationBuilder::link($this->urlGenerator->generate('financial_investments'), 'vers les finances →'),
		);

		$this->notificationRepository->save($notificationBuilder->for($player));

		$this->spendUniversityInvestments($player);
	}

	private function spendUniversityInvestments(Player $player): void
	{
		$research = $this->researchRepository->getPlayerResearch($player)
			?? throw new \LogicException('Player must have an associated Research entity');

		[
			Research::DOMAIN_NATURAL_SCIENCES => $naturalTechInvestedCredits,
			Research::DOMAIN_LIFE_SCIENCES => $lifeTechInvestedCredits,
			Research::DOMAIN_SOCIAL_POLITICAL_SCIENCES => $socialTechInvestedCredits,
			Research::DOMAIN_INFORMATIC_ENGINEERING => $informaticTechInvestedCredits,
		] = $this->computeInvestments($player);

		$bonusApplier = fn (int $investedCredits) => intval($this->bonusApplier->apply($investedCredits, PlayerBonusId::UNI_INVEST));

		$naturalTechInvestedCredits += $bonusApplier($naturalTechInvestedCredits);
		$lifeTechInvestedCredits += $bonusApplier($lifeTechInvestedCredits);
		$socialTechInvestedCredits += $bonusApplier($socialTechInvestedCredits);
		$informaticTechInvestedCredits += $bonusApplier($informaticTechInvestedCredits);

		$this->researchManager->update(
			$research,
			$player,
			$naturalTechInvestedCredits,
			$lifeTechInvestedCredits,
			$socialTechInvestedCredits,
			$informaticTechInvestedCredits,
		);
	}

	/**
	 * @return array<string, int>
	 */
	private function computeInvestments(Player $player): array
	{
		$computeInvestedCredits = fn (int $percent) => PercentageApplier::toInt($player->iUniversity, $percent);

		return [
			Research::DOMAIN_NATURAL_SCIENCES => $computeInvestedCredits($player->partNaturalSciences),
			Research::DOMAIN_LIFE_SCIENCES => $computeInvestedCredits($player->partLifeSciences),
			Research::DOMAIN_SOCIAL_POLITICAL_SCIENCES => $computeInvestedCredits($player->partSocialPoliticalSciences),
			Research::DOMAIN_INFORMATIC_ENGINEERING => $computeInvestedCredits($player->partInformaticEngineering),
		];
	}
}
