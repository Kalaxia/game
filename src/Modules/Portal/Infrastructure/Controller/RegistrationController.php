<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller;

use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Portal\Domain\Repository\UserRepositoryInterface;
use App\Modules\Portal\Infrastructure\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly UserPasswordHasherInterface $passwordHasher,
	) {
	}

	#[Route(
		path: '/registration',
		name: 'registration',
		methods: ['GET', 'POST'],
	)]
	public function __invoke(Request $request, UserRepositoryInterface $userRepository): Response
	{
		$user = new User();
		$form = $this->createForm(RegistrationFormType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			// Hashage du mot de passe
			$user->setPassword(
				$this->passwordHasher->hashPassword(
					$user,
					$form->get('password')->getData()
				)
			);

			$userRepository->save($user);

			$this->addFlash('success', 'Votre compte a été créé avec succès !');

			return $this->redirectToRoute('login');
		}

		return $this->render('pages/portal/registration.html.twig', [
			'registrationForm' => $form,
		]);
	}
}
