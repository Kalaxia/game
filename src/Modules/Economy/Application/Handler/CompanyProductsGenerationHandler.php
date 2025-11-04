<?php

declare(strict_types=1);

namespace App\Modules\Economy\Application\Handler;

use App\Modules\Economy\Application\Message\CompanyProductsGenerationMessage;
use App\Modules\Economy\Domain\Repository\CompanyRepositoryInterface;
use App\Modules\Economy\Domain\Service\GenerateCompanyProducts;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CompanyProductsGenerationHandler
{
	public function __construct(
		private CompanyRepositoryInterface $companyRepository,
		private GenerateCompanyProducts $generateCompanyProducts,
	) {
	}

	public function __invoke(CompanyProductsGenerationMessage $message): void
	{
		$company = $this->companyRepository->get($message->companyId)
			?? throw new \LogicException(sprintf('Company %s not found', $message->companyId->toRfc4122()));

		($this->generateCompanyProducts)($company);
	}
}
