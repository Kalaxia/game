<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Form;

use App\Modules\Portal\Domain\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('email', EmailType::class, [
				'label' => 'Email',
			])
			->add('username', TextType::class, [
				'label' => 'Identifiant',
			])
			->add('password', RepeatedType::class, [
				'type' => PasswordType::class,
				'first_options' => [
					'label' => 'Mot de passe',
				],
				'second_options' => [
					'label' => 'Confirmez le mot de passe',
				],
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => User::class,
		]);
	}
}
