<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Mission;

use App\Classes\Library\Format;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Model\Commander;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tells the players taking part in a battle that it starts, and how it ended for each of their officers.
 * The outcome of the missions (loot, conquest…) is notified by AccomplishMission.
 */
readonly class BattleNotifier
{
	public function __construct(
		private NotificationPersister $notificationPersister,
		private UrlGeneratorInterface $urlGenerator,
	) {
	}

	/**
	 * @param list<Commander> $commanders
	 */
	public function notifyStart(Battle $battle, array $commanders): void
	{
		foreach ($this->groupByPlayer($commanders) as [$player, $playerCommanders]) {
			$this->notify($player, 'Bataille engagée', NotificationBuilder::paragraph(
				sprintf('Une bataille a éclaté en orbite de %s. ', $this->getPlaceName($battle)),
				$this->getNames($playerCommanders),
				count($playerCommanders) > 1 ? ' y prennent part.' : ' y prend part.',
				NotificationBuilder::divider(),
				$this->getBattleLink($battle, 'suivre la bataille en direct'),
			));
		}
	}

	public function notifyJoin(Battle $battle, Commander $commander): void
	{
		$this->notify($commander->player, 'Renfort engagé', NotificationBuilder::paragraph(
			sprintf('Votre officier %s rejoint la bataille en orbite de %s.', NotificationBuilder::bold(htmlspecialchars($commander->name)), $this->getPlaceName($battle)),
			NotificationBuilder::divider(),
			$this->getBattleLink($battle, 'suivre la bataille en direct'),
		));
	}

	/**
	 * @param list<array{Commander, string}> $outcomes         each officer with what became of them, in a few words
	 * @param array<string, int>             $sidesByCommander side of each officer, by officer id
	 */
	public function notifyEnd(Battle $battle, array $outcomes, ?int $winningSide, array $sidesByCommander): void
	{
		$commanders = array_map(fn (array $outcome) => $outcome[0], $outcomes);

		foreach ($this->groupByPlayer($commanders) as [$player, $playerCommanders]) {
			$side = $sidesByCommander[$playerCommanders[0]->id->toRfc4122()] ?? null;
			$lines = array_map(
				fn (array $outcome) => sprintf(
					'%s : %s%s.',
					NotificationBuilder::bold(htmlspecialchars($outcome[0]->name)),
					$outcome[1],
					$outcome[0]->earnedExperience > 0 ? sprintf(', +%s XP', Format::number($outcome[0]->earnedExperience)) : '',
				),
				array_filter($outcomes, fn (array $outcome) => $outcome[0]->player?->id === $player->id),
			);

			$this->notify($player, match (true) {
				null === $winningSide => 'Bataille indécise',
				$side === $winningSide => 'Bataille remportée',
				default => 'Bataille perdue',
			}, NotificationBuilder::paragraph(
				sprintf('La bataille en orbite de %s est terminée.', $this->getPlaceName($battle)),
				NotificationBuilder::divider(),
				implode('<br>', $lines),
				NotificationBuilder::divider(),
				$this->getBattleLink($battle, 'voir le rapport et le replay'),
			));
		}
	}

	/**
	 * @param list<Commander> $commanders
	 *
	 * @return list<array{Player, list<Commander>}>
	 */
	private function groupByPlayer(array $commanders): array
	{
		$groups = [];

		foreach ($commanders as $commander) {
			if (null === $commander->player) {
				continue;
			}
			$groups[$commander->player->id] ??= [$commander->player, []];
			$groups[$commander->player->id][1][] = $commander;
		}

		return array_values($groups);
	}

	/**
	 * @param list<Commander> $commanders
	 */
	private function getNames(array $commanders): string
	{
		return implode(', ', array_map(fn (Commander $commander) => NotificationBuilder::bold(htmlspecialchars($commander->name)), $commanders));
	}

	private function getPlaceName(Battle $battle): string
	{
		$planet = $battle->place;

		return NotificationBuilder::link(
			$this->urlGenerator->generate('map', ['place' => $planet->id]),
			null !== $planet->player ? htmlspecialchars($planet->name) : 'la planète rebelle',
		);
	}

	private function getBattleLink(Battle $battle, string $label): string
	{
		return NotificationBuilder::link($this->urlGenerator->generate('battle_view', ['id' => $battle->id]), $label);
	}

	private function notify(Player $player, string $title, string $content): void
	{
		$this->notificationPersister->saveFromBuilder(
			NotificationBuilder::new()->setTitle($title)->setContent($content)->forPlayer($player),
		);
	}
}
