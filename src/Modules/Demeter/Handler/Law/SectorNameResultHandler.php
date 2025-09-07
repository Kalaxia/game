<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\SectorNameResultMessage;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class SectorNameResultHandler
{
	public function __construct(
		private LawRepositoryInterface $lawRepository,
		private SectorRepositoryInterface $sectorRepository,
	) {
	}

	public function __invoke(SectorNameResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$faction = $law->faction;
		$sector = $this->sectorRepository->get(Uuid::fromString($law->options['rSector']));

		if ($sector->faction->id->equals($faction->id)) {
			$sector->name = $law->options['name'];
		}
		$law->statement = Law::OBSOLETE;

		$this->sectorRepository->save($sector);
		$this->lawRepository->save($law);
	}
}
