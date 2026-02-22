<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\Law\LawManager;
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
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class VoteHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private DurationHandler $durationHandler,
		private LawRepositoryInterface $lawRepository,
		private LawManager $lawManager,
		private MessageBusInterface $messageBus,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(VoteMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId())
			?? throw new \RuntimeException(sprintf('Law %s has not been found when processing the vote results', $message->getLawId()->toRfc4122()));

		$faction = $law->faction;
		$ballot = $this->lawManager->ballot($law);
		if ($ballot) {
			// accepter la loi
			$law->statement = Law::EFFECTIVE;
			// envoyer un message
		} else {
			// refuser la loi
			$law->statement = Law::REFUSED;
			if (LawResources::getInfo($law->type, 'bonusLaw')) {
				$activePlayers = $this->playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]);
				$faction->credits += intval(round((LawResources::getInfo($law->type, 'price') * $this->durationHandler->getHoursDiff($law->voteEndedAt, $law->endedAt) * ($activePlayers + 1) * 90) / 100));
			} else {
				$faction->credits += intval(round((LawResources::getInfo($law->type, 'price') * 90) / 100));
			}
			// envoyer un message
		}
		$this->lawRepository->save($law);
		$this->colorRepository->save($faction);

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
