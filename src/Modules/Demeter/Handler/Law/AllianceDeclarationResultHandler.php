<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\AllianceDeclarationResultMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class AllianceDeclarationResultHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CommercialRouteManager $commercialRouteManager,
		private LawRepositoryInterface $lawRepository,
	) {
	}

	public function __invoke(AllianceDeclarationResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$color = $law->faction;
		$this->colorRepository->refresh($color);
		$enemyColor = $this->colorRepository->getOneByIdentifier($law->options['rColor']);

		$color->relations[$law->options['rColor']] = Color::ALLY;

		$law->statement = Law::OBSOLETE;
		$this->commercialRouteManager->toggleRoutesFreeze($color, $enemyColor);
		$this->colorRepository->save($color);
		$this->lawRepository->save($law);
	}
}
