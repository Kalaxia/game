<?php

declare(strict_types=1);

namespace App\Modules\Economy\Application\Message;

use Symfony\Component\Uid\Uuid;

readonly class ProductOfferIndexMessage
{
	public function __construct(
		public Uuid $productId,
	) {
	}
}
