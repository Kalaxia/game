<?php

namespace App\Modules\Hermes\Application\Builder;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Domain\Entity\News;
use App\Modules\Hermes\Domain\Enum\NewsType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Uid\Uuid;

final class NewsBuilder
{
	private NewsType $type;
	private Color $source;
	/** @var array<int, Color> */
	private array $recipients = [];
	/** @var array<string, mixed> */
	private array $data = [];

	public static function new(): self
	{
		return new self();
	}

	public function setType(NewsType $type): self
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function setData(array $data): static
	{
		$this->data = $data;

		return $this;
	}

	public function fromFaction(Color $faction): static
	{
		$this->source = $faction;

		return $this;
	}

	public function forFaction(Color $faction): static
	{
		$this->recipients[$faction->id->toRfc4122()] = $faction;

		return $this;
	}

	public function forFactions(array $factions): static
	{
		foreach ($factions as $faction) {
			$this->forFaction($faction);
		}

		return $this;
	}

	public function build(): array
	{
		return array_map(
			fn (Color $faction) => new News(
				id: Uuid::v4(),
				source: $this->source,
				recipient: $faction,
				type: $this->type,
				createdAt: new DatePoint(),
				data: $this->data,
			),
			$this->recipients,
		);
	}
}
