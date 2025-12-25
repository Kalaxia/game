<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Demeter\Model\Color;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Zeus\Model\CreditHolderInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'economy__companies')]
#[ORM\UniqueConstraint(columns: ['slug'])]
class Company implements CreditHolderInterface
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\Column(type: 'string')]
		public string $name,
		#[ORM\Column(type: 'string')]
		public string $slug,
		#[ORM\ManyToOne(targetEntity: Color::class)]
		public Color $faction,
		#[ORM\Column(type: 'string', enumType: Activity::class, length: 32)]
		public Activity $activity,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $updatedAt,
		#[ORM\Column(type: 'integer')]
		public int $credits = 0,
	) {

	}

	public function setCredits(int $credit): static
	{
		$this->credits = $credit;

		return $this;
	}

	public function getCredits(): int
	{
		return $this->credits;
	}

	public function canAfford(int $amount): bool
	{
		if ($amount > $this->credits) {
			return false;
		}
		return true;
	}
}
