<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Foundry\Factory;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Service\GenerateCompanyProducts;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class CompanyFactory extends PersistentObjectFactory
{
	public function __construct(
		private readonly GenerateCompanyProducts $generateCompanyProducts,
		private readonly SluggerInterface $slugger,
	) {
		parent::__construct();
	}

	protected function initialize(): static
	{
		return $this->afterPersist(function (Company $company) {
			($this->generateCompanyProducts)($company);
		});
	}

	protected function defaults(): array|callable
	{
		$companyName = self::faker()->unique()->company();

		return [
			'id' => Uuid::v4(),
			'name' => $companyName,
			'slug' => $this->slugger->slug($companyName),
			'activity' => self::faker()->randomElement(Activity::cases()),
			'faction' => FactionFactory::random(),
			'credits' => self::faker()->randomNumber(1) * 1000000,
			'createdAt' => new \DateTimeImmutable(),
			'updatedAt' => new \DateTimeImmutable(),
		];
	}

	public static function class(): string
	{
		return Company::class;
	}
}
