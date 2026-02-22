<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\WarDeclarationResultMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class WarDeclarationResultHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CommercialRouteManager $commercialRouteManager,
		private LawRepositoryInterface $lawRepository,
	) {
	}

	public function __invoke(WarDeclarationResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$color = $law->faction;
		$this->colorRepository->refresh($color);
		$enemyColor = $this->colorRepository->getOneByIdentifier($law->options['rColor']);

		$enemyColor->relations[$color->identifier] = Color::ENEMY;
		$color->relations[$law->options['rColor']] = Color::ENEMY;
		$law->statement = Law::OBSOLETE;
		$this->commercialRouteManager->toggleRoutesFreeze($color, $enemyColor);
		$this->colorRepository->save($color);
		$this->lawRepository->save($law);
	}
}
