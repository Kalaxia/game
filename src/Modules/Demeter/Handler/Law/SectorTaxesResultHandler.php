<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\SectorTaxesResultMessage;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class SectorTaxesResultHandler
{
	public function __construct(
		private LawRepositoryInterface $lawRepository,
		private SectorRepositoryInterface $sectorRepository,
	) {
	}

	public function __invoke(SectorTaxesResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$faction = $law->faction;
		$sector = $this->sectorRepository->get(Uuid::fromString($law->options['rSector']));

		if ($sector->faction->id->equals($faction->id)) {
			$sector->tax = $law->options['taxes'];
		}
		$law->statement = Law::OBSOLETE;

		$this->lawRepository->save($law);
		$this->sectorRepository->save($sector);
	}
}
