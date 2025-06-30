<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Factory;

use App\Classes\Library\Parser;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Domain\Service\Law\GetApplicationDuration;
use App\Modules\Demeter\Domain\Service\Law\GetVotationTime;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

readonly class LawFactory
{
	public function __construct(
		private ClockInterface $clock,
		private ColorManager                     $colorManager,
		private ColorRepositoryInterface $colorRepository,
		private CommercialTaxRepositoryInterface $commercialTaxRepository,
		private GetApplicationDuration $getApplicationDuration,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private GetVotationTime $getVotationTime,
		private PlayerRepositoryInterface        $playerRepository,
		private SectorRepositoryInterface        $sectorRepository,
		private LawRepositoryInterface           $lawRepository,
		private Parser                           $parser,
		#[Autowire('%politics_law_max_duration%')]
		private int $lawMaxDuration,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function createFromPayload(
		int      $type,
		int|null $duration,
		Player   $player,
		array    $payload
	): Law {
		$faction = $player->faction;
		$isRulerLaw = Player::CHIEF === LawResources::getInfo($type, 'department');
		$statement = $isRulerLaw ? Law::EFFECTIVE : Law::VOTATION;

		$voteEndedAt = $isRulerLaw
			? $this->clock->now()
			: new DatePoint('+' . ($this->getVotationTime)() . ' seconds');

		$applicationMode = LawResources::getInfo($type, 'application_mode');
		$lawName = LawResources::getInfo($type, 'name');

		if (LawResources::APPLICATION_MODE_INSTANTANEOUS === $applicationMode && null !== $duration) {
			throw new \InvalidArgumentException(sprintf('Law "%s" cannot have a custom duration', $lawName));
		}

		if (LawResources::APPLICATION_MODE_CUSTOM_DURATION === $applicationMode && null === $duration) {
			throw new \InvalidArgumentException(sprintf('Law "%s" must have a custom duration', $lawName));
		}

		// TODO replace bonusLaw with application_mode
		if (LawResources::getInfo($type, 'bonusLaw')) {
			$options = [];

			if ($this->lawRepository->lawExists($player->faction, $type)) {
				throw new \DomainException('Cette loi est déjà proposée ou en vigueur.');
			}

			return new Law(
				id: Uuid::v4(),
				faction: $faction,
				type: $type,
				voteEndedAt: $voteEndedAt,
				endedAt: ($this->getApplicationDuration)($type, $duration),
				createdAt: new \DateTimeImmutable(),
				options: $options,
				statement: $statement,
			);
		}

		$options = match ($type) {
			Law::SECTORTAX => $this->createSectorTaxLaw($player, $payload),
			Law::SECTORNAME => $this->createSectorNameLaw($player, $payload),
			Law::COMTAXEXPORT => $this->createCommercialExportTaxLaw($player, $payload),
			Law::COMTAXIMPORT => $this->createCommercialImportTaxLaw($player, $payload),
			Law::NEUTRALPACT => $this->createNeutralPactLaw($faction, $payload),
			Law::PEACEPACT => $this->createPeacePactLaw($faction, $payload),
			Law::TOTALALLIANCE => $this->createTotalAllianceLaw($faction, $payload),
			Law::WARDECLARATION => $this->createWarDeclarationLaw($faction, $payload),
			Law::PUNITION => $this->createPunitionLaw($player, $payload),
			default => throw new \InvalidArgumentException(sprintf('La loi %s n\'est pas enregistrée.', $type)),
		};

		return new Law(
			id: Uuid::v4(),
			faction: $faction,
			type: $type,
			voteEndedAt: $voteEndedAt,
			endedAt: ($this->getApplicationDuration)($type, $duration),
			createdAt: new \DateTimeImmutable(),
			options: $options,
			statement: $statement,
		);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private function createSectorTaxLaw(Player $currentPlayer, array $payload): array
	{
		$taxes = $payload['taxes']
			?? throw new \InvalidArgumentException('Missing taxes amount');
		$rSector = $payload['rsector']
			?? throw new \InvalidArgumentException('Missing sector id');
		// TODO replace with specification
		if ($taxes < 2 || $taxes > 15) {
			throw new \DomainException('La taxe doit être entre 2 et 15 %.');
		}
		$sector = $this->sectorRepository->get(Uuid::fromString($rSector))
			?? throw new \InvalidArgumentException('Sector not found');
		// TODO Replace with Voter
		if (!$sector->faction->id->equals($currentPlayer->faction->id)) {
			// TODO Replace with custom exception
			throw new \UnexpectedValueException('Ce secteur n\'est pas sous votre contrôle.');
		}
		return [
			'taxes' => $taxes,
			'rSector' => $rSector,
			'display' => [
				'Secteur' => $sector->name,
				'Taxe actuelle' => $sector->tax . ' %',
				'Taxe proposée' => $taxes . ' %'
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createSectorNameLaw(Player $currentPlayer, array $payload): array
	{
		$rSector = $payload['rsector'] ?? throw new \InvalidArgumentException('Missing sector');
		$name = $payload['name'] ?? throw new \InvalidArgumentException('Missing name');
		// Replace with specification
		if (strlen($name) < 1 and strlen($name) > 50) {
			throw new \DomainException('Le nom doit faire entre 1 et 50 caractères.');
		}
		$name = $this->parser->protect($name);
		$sector = $this->sectorRepository->get(Uuid::fromString($rSector))
			?? throw new \InvalidArgumentException('Ce secteur n\'existe pas.');
		if (!$sector->faction->id->equals($currentPlayer->faction->id)) {
			throw new \UnexpectedValueException('Ce secteur n\'est pas sous votre contrôle.');
		}
		return [
			'name' => $name,
			'rSector' => $rSector,
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createCommercialExportTaxLaw(Player $currentPlayer, array $payload): array
	{
		$taxes = $payload['taxes']
			?? throw new \InvalidArgumentException('Missing taxes');
		$rColor = $payload['rcolor']
			?? throw new \InvalidArgumentException('Missing related faction');
		$relatedFaction = $this->colorRepository->get(Uuid::fromString($rColor))
			?? throw new \InvalidArgumentException('Faction not found');

		$commercialTaxes = $this->commercialTaxRepository->getFactionsTax($currentPlayer->faction, $relatedFaction);

		if ($relatedFaction->id->equals($currentPlayer->faction->id) && $taxes > 15) {
			throw new \DomainException('Pas plus que 15.');
		} elseif ($taxes > 15 || $taxes < 2) {
			throw new \DomainException('Entre 2 et 15.');
		}

		return [
			'taxes' => $taxes,
			'rColor' => $rColor,
			'display' => [
				'Faction' => ($this->getFactionsConfiguration)($relatedFaction, 'officialName'),
				'Taxe actuelle' => $commercialTaxes->exportTax . ' %',
				'Taxe proposée' => $taxes . ' %',
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createCommercialImportTaxLaw(Player $currentPlayer, array $payload): array
	{
		$taxes = $payload['taxes']
			?? throw new \InvalidArgumentException('Missing taxes');
		$rColor = $payload['rcolor']
			?? throw new \InvalidArgumentException('Missing related faction');
		$relatedFaction = $this->colorRepository->get(Uuid::fromString($rColor))
			?? throw new \InvalidArgumentException('Faction not found');

		$commercialTaxes = $this->commercialTaxRepository->getFactionsTax($currentPlayer->faction, $relatedFaction);

		if ($relatedFaction->id->equals($currentPlayer->faction->id) && $taxes > 15) {
			throw new \DomainException('Pas plus que 15.');
		} elseif ($taxes > 15 || $taxes < 2) {
			throw new \DomainException('Entre 2 et 15.');
		}
		return [
			'taxes' => $taxes,
			'rColor' => $rColor,
			'display' => [
				'Faction' => ($this->getFactionsConfiguration)($relatedFaction, 'officialName'),
				'Taxe actuelle' => $commercialTaxes->importTax . ' %',
				'Taxe proposée' => $taxes . ' %',
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createNeutralPactLaw(Color|null $faction, array $payload): array
	{
		$factionIdentifier = intval($payload['rcolor']
			?? throw new \InvalidArgumentException('Informations manquantes.'));

		if ($factionIdentifier === $faction->identifier) {
			throw new \InvalidArgumentException('Il s\'agit de votre propre faction.');
		}

		$relatedFaction = $this->colorRepository->getOneByIdentifier($factionIdentifier)
			?? throw new \InvalidArgumentException('Faction not found');

		if (Color::NEUTRAL === $faction->relations[$factionIdentifier]) {
			throw new \DomainException('Vous considérez déjà cette faction comme neutre.');
		}

		return [
			'rColor' => $factionIdentifier,
			'display' => [
				'Faction' => ($this->getFactionsConfiguration)($relatedFaction, 'officialName'),
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createPeacePactLaw(Color|null $faction, array $payload): array
	{
		$factionIdentifier = intval($payload['rcolor']
			?? throw new \InvalidArgumentException('Informations manquantes.'));
		if ($factionIdentifier === $faction->identifier) {
			throw new \InvalidArgumentException('Il s\'agit de votre propre faction.');
		}
		$nbrPact = 0;
		foreach ($faction->relations as $relation) {
			if (Color::PEACE == $relation) {
				++$nbrPact;
			}
		}
		if ($nbrPact >= 2) {
			throw new \DomainException('Vous ne pouvez faire que 2 pactes de ce type.');
		}

		if (Color::PEACE === $faction->relations[$factionIdentifier]) {
			throw new \DomainException('Vous considérez déjà cette faction comme votre alliée.');
		}

		$relatedFaction = $this->colorRepository->getOneByIdentifier($factionIdentifier)
			?? throw new \InvalidArgumentException('Faction not found');

		return [
			'rColor' => $factionIdentifier,
			'display' => [
				'Faction' => ($this->getFactionsConfiguration)($relatedFaction, 'officialName'),
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createTotalAllianceLaw(Color|null $faction, array $payload): array
	{
		$factionIdentifier = intval($payload['rcolor']
			?? throw new \InvalidArgumentException('Informations manquantes.'));
		if ($factionIdentifier === $faction->identifier) {
			throw new \InvalidArgumentException('Il s\'agit de votre propre faction.');
		}
		$allyYet = false;
		foreach ($faction->relations as $relation) {
			if (Color::ALLY === $relation) {
				$allyYet = true;
			}
		}
		if ($allyYet) {
			throw new \DomainException('Vous ne pouvez considérez qu\'une seule faction comme alliée.');
		}

		if (Color::ALLY === $faction->relations[$factionIdentifier]) {
			throw new \DomainException('Vous considérez déjà cette faction comme votre alliée.');
		}

		$relatedFaction = $this->colorRepository->getOneByIdentifier($factionIdentifier)
			?? throw new \InvalidArgumentException('Faction not found');

		return [
			'rColor' => $factionIdentifier,
			'display' => [
				'Faction' => ($this->getFactionsConfiguration)($relatedFaction, 'officialName'),
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createWarDeclarationLaw(Color|null $faction, array $payload): array
	{
		$factionIdentifier = intval($payload['rcolor']
			?? throw new \InvalidArgumentException('Informations manquantes.'));

		if ($factionIdentifier === $faction->identifier) {
			throw new \InvalidArgumentException('Il s\'agit de votre propre faction.');
		}
		if (Color::ENEMY === $faction->relations[$factionIdentifier]) {
			throw new \DomainException('Vous considérez déjà cette faction comme votre ennemie.');
		}

		$relatedFaction = $this->colorRepository->getOneByIdentifier($factionIdentifier)
			?? throw new \InvalidArgumentException('Faction not found');

		return [
			'rColor' => $factionIdentifier,
			'display' => [
				'Faction' => ($this->getFactionsConfiguration)($relatedFaction, 'officialName')
			],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createPunitionLaw(Player $currentPlayer, array $payload): array
	{
		$rPlayer = $payload['rplayer']
			?? throw new \InvalidArgumentException('Missing player id');
		$credits = intval($payload['credits']
			?? throw new \InvalidArgumentException('Missing fine amount'));

		if ($credits <= 0) {
			throw new \InvalidArgumentException('l\'amende doit être un entier positif.');
		}
		$targetPlayer = $this->playerRepository->get($rPlayer);
		if (!$targetPlayer->faction->id->equals($currentPlayer->faction->id)) {
			throw new \UnexpectedValueException('Ce joueur n\'est pas de votre faction.');
		}

		return [
			'rPlayer' => $rPlayer,
			'credits' => $credits,
			'display' => [
				'Joueur' => $targetPlayer->name,
				'amende' => $credits,
			],
		];
	}
}
