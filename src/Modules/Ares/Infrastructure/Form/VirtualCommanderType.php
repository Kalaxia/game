<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Form;

use App\Modules\Ares\Domain\Model\VirtualCommander;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

final class VirtualCommanderType extends AbstractType
{
	public function __construct(
		#[Autowire('%app.factions%')]
		private readonly array $availableFactions,
		private readonly RequestStack $requestStack,
		private readonly ColorRepositoryInterface $colorRepository,
	) {
	}

	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('id', HiddenType::class)
			->add('name')
			->add('level', IntegerType::class, [
				'data' => 10,
			])
			->add('faction', ChoiceType::class, [
				'choices' => array_map(
					fn (int $identifier) => $this->colorRepository->getOneByIdentifier($identifier),
					array_keys($this->availableFactions),
				),
				'choice_label' => fn (Color $choice) => new TranslatableMessage(sprintf(
					'factions.%d.name.official',
					$choice->identifier,
				)),
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => VirtualCommander::class,
			'empty_data' => fn (FormInterface $form) => new VirtualCommander(
				id: count($this->requestStack->getSession()->get('combat_simulator.commanders', [])),
				name: $form->get('name')->getData(),
				level: $form->get('level')->getData(),
				faction: $form->get('faction')->getData(),
			),
		]);
	}
}
