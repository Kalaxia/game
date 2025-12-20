<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\DataFixtures\Factory;

use App\Modules\Portal\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class UserFactory extends PersistentObjectFactory
{
	public function __construct(
		private readonly UserPasswordHasherInterface $userPasswordHasher,
	) {
	}

	protected function defaults(): array
	{
		return [
			'password' => 'test',
			'email' => self::faker()->email(),
		];
	}

	protected function initialize(): static
	{
		return $this->beforeInstantiate(function (array $attributes): array {
			$attributes['password'] = $this->userPasswordHasher->hashPassword(
				new User(),
				$attributes['password'],
			);

			return $attributes;
		});
	}

	public static function class(): string
	{
		return User::class;
	}
}
