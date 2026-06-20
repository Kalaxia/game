<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Twig\Components\Organisms\CombatSimulator;

use App\Modules\Ares\Domain\Model\VirtualCommander;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
	name: 'SimulatorCommandersList',
	template: 'components/Organisms/Portal/CombatSimulator/CommandersList.html.twig',
)]
final class CommandersList
{
	use DefaultActionTrait;
	use ComponentToolsTrait;

	/** @var VirtualCommander[] */
	#[LiveProp(useSerializerForHydration: true)]
	public array $commanders = [];

	#[LiveAction]
	public function remove(
		Request $request,
		#[LiveArg]
		int $id,
	): void {
		$session = $request->getSession();

		$commanders = array_filter(
			$session->get('combat_simulator.commanders', []),
			fn (VirtualCommander $commander) => $commander->id !== $id,
		);
		$session->set('combat_simulator.commanders', $commanders);

		$this->commanders = $commanders;
	}

	#[LiveListener('commanderAdded')]
	public function onCommanderAdded(
		Request $request,
	): void {
		$session = $request->getSession();

		$this->commanders = $session->get('combat_simulator.commanders', []);
	}
}
