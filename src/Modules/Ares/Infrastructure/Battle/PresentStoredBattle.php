<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Battle;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Ares\Domain\Repository\BattleTurnRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubInterface;

/**
 * A stored battle as seen by the viewer so far, with what it needs to follow the next turns:
 * the Mercure topic of the battle, and a cookie authorising the viewer to subscribe to it.
 * Callers check that the viewer may watch the battle (Q17).
 */
readonly class PresentStoredBattle
{
	public function __construct(
		private BattleTurnRepositoryInterface $battleTurnRepository,
		private PresentBattle $presentBattle,
		private HubInterface $hub,
		private Authorization $authorization,
	) {
	}

	/**
	 * @param array<string, mixed> $live extra data for the live viewer
	 *
	 * @return array<string, mixed>
	 */
	public function __invoke(Request $request, Battle $battle, array $live = []): array
	{
		$topic = MercureBattleUpdatePublisher::getTopic($battle->id);
		$this->authorization->setCookie($request, [$topic]);

		$turns = array_map(fn (BattleTurn $turn) => $turn->toResult(), $this->battleTurnRepository->getBattleTurns($battle));

		return [
			...($this->presentBattle)($battle->getState(), $turns, $battle->turnDuration),
			'live' => [
				'id' => $battle->id->toRfc4122(),
				'hub_url' => $this->hub->getPublicUrl(),
				'topic' => $topic,
				'is_over' => $battle->isOver(),
				...$live,
			],
		];
	}
}
