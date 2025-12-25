<?php

declare(strict_types=1);

namespace App\Modules\Economy\Application\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class CompanyProductsGenerationMessage implements AsyncMessage
{
	public function __construct(
		public Uuid $companyId,
	) {
	}
}
