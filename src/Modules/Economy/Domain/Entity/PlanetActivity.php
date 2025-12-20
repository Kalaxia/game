<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Entity;

use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * This entity represents the performing of a certain activity for the given company on the given planet.
 *
 * The company needs this to produce the products related to the activity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'economy__planet_activities')]
#[ORM\UniqueConstraint(columns: ['planet_id', 'activity'])]
class PlanetActivity
{
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne(targetEntity: Planet::class)]
		public Planet $planet,
		#[ORM\Column(type: 'string', enumType: Activity::class, length: 32)]
		public Activity $activity,
		#[ORM\ManyToOne(targetEntity: Company::class)]
		public Company $company,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $updatedAt,
	) {
	}
}
