<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Security;

use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Portal\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

readonly class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
	public function __construct(
		private UserRepositoryInterface $userRepository,
	) {
	}

	public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
	{
		/** @var User $user */
		$user = $token->getUser();
		$user->setLastLoggedAt(new \DateTimeImmutable());

		$this->userRepository->save($user);

		return new RedirectResponse($request->request->get('_target_path'));
	}
}
