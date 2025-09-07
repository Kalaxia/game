<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Exception;
use Symfony\Component\Uid\Uuid;

interface CommercialRouteRepositoryInterface extends EntityRepositoryInterface
{
	public function get(int $id): CommercialRoute|null;

	/**
	 * @param list<int> $factions
	 * @return list<array<string, mixed>>
	 *
	 * @throws Exception
	 */
	public function searchCandidates(
        Player $player,
        Planet $planet,
        array  $factions,
        int    $minDistance,
        int    $maxDistance,
	): array;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getAllPlayerRoutes(Player $player): array;

	public function countCommercialRoutesBetweenFactions(Color $faction, Color $otherFaction): int;

	public function getCommercialRouteFactionData(Color $faction): array;

	public function getInternalCommercialRouteFactionData(Color $faction): array;

	public function getByIdAndPlanet(Uuid $id, Planet $planet): CommercialRoute|null;

	public function getByIdAndDistantPlanet(Uuid $id, Planet $planet): CommercialRoute|null;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByPlanet(Planet $planet): array;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByDistantPlanet(Planet $planet): array;

	public function getExistingRoute(Planet $planet, Planet $distantPlanet): CommercialRoute|null;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getPlanetRoutes(Planet $planet): array;

	public function getPlanetIncome(Planet $planet): int;

	/**
	 * @param list<int> $statements
	 */
	public function countPlanetRoutes(Planet $planet, array $statements = []): int;

	public function freezeRoutes(Color $faction, Color $otherFaction, bool $freeze): void;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getPlayerConstructedRoutesSince(Player $player, \DateTimeImmutable $since): array;
}
