<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\DataFixtures\Factory;

use App\Modules\Portal\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
class UserFactory extends PersistentProxyObjectFactory
{
	public function __construct(
		private readonly ?UserPasswordHasherInterface $passwordHasher = null,
	) {
		parent::__construct();
	}

	protected function initialize(): static
	{
		return $this->afterInstantiate(function (User $user) {
			if (null !== $this->passwordHasher) {
				$user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
			}
		});
	}

	protected function defaults(): array|callable
	{
		return [
			'username' => self::faker()->userName(),
			'email' => self::faker()->email(),
			'password' => 'testpassword',
		];
	}

	public static function class(): string
	{
		return User::class;
	}
}
