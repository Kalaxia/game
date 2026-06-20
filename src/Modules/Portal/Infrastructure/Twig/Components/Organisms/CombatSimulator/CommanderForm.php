<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Twig\Components\Organisms\CombatSimulator;

use App\Modules\Ares\Domain\Model\VirtualCommander;
use App\Modules\Ares\Infrastructure\Form\VirtualCommanderType;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
	name: 'SimulatorCommanderForm',
	template: 'components/Organisms/Portal/CombatSimulator/CommanderForm.html.twig',
)]
class CommanderForm extends AbstractController
{
	use DefaultActionTrait;
	use ComponentWithFormTrait;
	use ComponentToolsTrait;

	#[LiveProp]
	public ?VirtualCommander $initialFormData = null;

	protected function instantiateForm(): FormInterface
	{
		return $this->createForm(VirtualCommanderType::class, $this->initialFormData);
	}

	#[LiveListener('beginUpdate')]
	public function onBeginUpdate(
		Request $request,
		#[LiveArg]
		int $commanderId,
	): void {
		$commander = $this->findCommander($request->getSession(), $commanderId);

		if (null === $commander) {
			return;
		}

		$this->formValues['id'] = $commander->id;
		$this->formValues['name'] = $commander->name;
		$this->formValues['level'] = $commander->level;
		$this->formValues['faction'] = $commander->faction->identifier;

		$this->dispatchBrowserEvent('modal:open');
	}

	#[LiveAction]
	public function save(
		Request $request,
		ColorRepositoryInterface $factionRepository,
	): void {
		$this->submitForm();

		$session = $request->getSession();
		$commanders = $session->get('combat_simulator.commanders', []);

		$commander = $this->getForm()->getData();
		$commander->id ??= count($commanders);

		$existingCommander = $this->findCommander($session, $commander->id);

		if (null === $existingCommander) {
			array_push($commanders, $commander);
		} else {
			foreach ($commanders as $index => $c) {
				if ($c->id === $existingCommander->id) {
					$commanders[$index] = $commander;
				}
			}
		}

		$session->set('combat_simulator.commanders', $commanders);

		$this->emit('commanderAdded', [
			'commander' => $commander,
		], componentName: 'SimulatorCommandersList');

		$this->dispatchBrowserEvent('modal:close');

		$this->resetForm();
	}

	private function findCommander(Session $session, int $id): ?VirtualCommander
	{
		$commanders = $session->get('combat_simulator.commanders', []);

		return array_find(
			$commanders,
			fn (VirtualCommander $commander) => $commander->id === $id,
		);
	}
}
