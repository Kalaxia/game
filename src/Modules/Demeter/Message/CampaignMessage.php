<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Message;

use App\Shared\Domain\Message\AsyncMessage;
use App\Shared\Domain\Message\UniqueMessage;
use Symfony\Component\Uid\Uuid;

readonly class CampaignMessage implements AsyncMessage, UniqueMessage
{
	public function __construct(
		public Uuid $factionId,
		public \DateTimeImmutable $nextCampaignStartedAt,
	) {
	}

	public function getUniqueId(): string
	{
		return sprintf(
			'campaign.%s.%s',
			$this->factionId->toRfc4122(),
			md5($this->nextCampaignStartedAt->format('c')),
		);
	}
}
