<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\AllianceDeclarationResultMessage;
use App\Modules\Demeter\Message\Law\BonusEndMessage;
use App\Modules\Demeter\Message\Law\ExportCommercialTaxesResultMessage;
use App\Modules\Demeter\Message\Law\ImportCommercialTaxesResultMessage;
use App\Modules\Demeter\Message\Law\NonAgressionPactDeclarationResultMessage;
use App\Modules\Demeter\Message\Law\PeaceDeclarationResultMessage;
use App\Modules\Demeter\Message\Law\SanctionResultMessage;
use App\Modules\Demeter\Message\Law\SectorNameResultMessage;
use App\Modules\Demeter\Message\Law\SectorTaxesResultMessage;
use App\Modules\Demeter\Message\Law\VoteMessage;
use App\Modules\Demeter\Message\Law\WarDeclarationResultMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\LawResources;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class LawEffectivenessScheduler implements SchedulerInterface
{
	public function __construct(
		private ColorRepositoryInterface $factionRepository,
		private LawRepositoryInterface $lawRepository,
		private MessageBusInterface $messageBus,
	) {
	}

	public function schedule(): void
	{
		foreach ($this->factionRepository->getInGameFactions() as $faction) {
			$this->scheduleFactionLaws($faction);
		}
	}

	private function scheduleFactionLaws(Color $faction): void
	{
		$laws = $this->lawRepository->getByFactionAndStatements($faction, [Law::VOTATION, Law::EFFECTIVE]);

		$now = new \DateTimeImmutable();

		foreach ($laws as $law) {
			if ($law->isBeingVoted() && $law->voteEndedAt < $now) {
				$this->messageBus->dispatch(
					new VoteMessage($law->id),
					[DateTimeConverter::to_delay_stamp($law->voteEndedAt)],
				);
			} elseif ($law->isEffective() && $law->endedAt < $now) {
				$messageClass = match (LawResources::getInfo($law->type, 'bonusLaw')) {
					true => BonusEndMessage::class,
					false => match ($law->type) {
						Law::SECTORTAX => SectorTaxesResultMessage::class,
						Law::SECTORNAME => SectorNameResultMessage::class,
						Law::COMTAXEXPORT => ExportCommercialTaxesResultMessage::class,
						Law::COMTAXIMPORT => ImportCommercialTaxesResultMessage::class,
						Law::PEACEPACT => PeaceDeclarationResultMessage::class,
						Law::WARDECLARATION => WarDeclarationResultMessage::class,
						Law::TOTALALLIANCE => AllianceDeclarationResultMessage::class,
						Law::NEUTRALPACT => NonAgressionPactDeclarationResultMessage::class,
						Law::PUNITION => SanctionResultMessage::class,
					},
				};
				$this->messageBus->dispatch(
					new $messageClass($law->id),
					[DateTimeConverter::to_delay_stamp($law->endedAt)],
				);
			}
		}
	}
}
