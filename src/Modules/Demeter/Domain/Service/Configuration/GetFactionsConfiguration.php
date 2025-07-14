<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Configuration;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GetFactionsConfiguration
{
	public function __construct(
		private TranslatorInterface $translator,
		#[Autowire('%app.factions%')]
		private array $factionsConfiguration,
	) {
	}

	public function __invoke(Color|int $faction, string $info): mixed
	{
		$identifier = ($faction instanceof Color) ? $faction->identifier : $faction;

		return $this->factionsConfiguration[$identifier][$info]
			?? $this->tryToTranslate($identifier, $info)
			?? throw new \InvalidArgumentException(sprintf(
				'Faction configuration %s not found for Faction %d',
				$info,
				$identifier,
			));
	}

	#[\Deprecated(message: 'Use the translator directly instead of this method')]
	private function tryToTranslate(int $identifier, string $info): array|string|null
	{
		if ($info === 'status') {
			return array_map(
				fn ($key) => $this->translator->trans(sprintf('factions.%d.status.%s', $identifier, $key)),
				[
					'player',
                    'elite',
                    'treasurer',
                    'commander',
                    'minister',
                    'leader',
				],
			);
		}

		$key = match ($info) {
			'officialName' => 'name.official',
            'popularName' => 'name.popular',
            'government' => 'name.government',
            'demonym' => 'name.demonym',
			'devise' => 'devise',
			'factionPoints' => 'faction_points',
            'desc1' => 'descriptions.general',
            'desc2' => 'descriptions.morals',
            'desc3' => 'descriptions.war',
            'desc4' => 'descriptions.culture',
            'senate' => 'descriptions.senate',
            'campaign' => 'descriptions.campaign',
		};

		if (null === $key) {
			return null;
		}

		return $this->translator->trans(sprintf('factions.%d.%s', $identifier, $key));
	}
}
