<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Galaxy\Application\Message\SectorGenerationMessage;
use App\Modules\Galaxy\Application\Message\SystemGenerationMessage;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Shared\Application\Service\GetProportion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SectorGenerationHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private GetProportion $getProportion,
		private SectorRepositoryInterface $sectorRepository,
		private MessageBusInterface $messageBus,
		private GalaxyConfiguration $galaxyConfiguration,
		private LoggerInterface $galaxyGenerationLogger,
	) {
	}

	public function __invoke(SectorGenerationMessage $message): void
	{
		$faction = (null !== $message->factionIdentifier)
			? $this->colorRepository->getOneByIdentifier($message->factionIdentifier)
				?? throw new \LogicException('Faction not found') : null;

		$sector = new Sector(
			id: Uuid::v4(),
			identifier: $message->identifier,
			faction: $faction,
			xPosition: $message->xPosition,
			yPosition: $message->yPosition,
			xBarycentric: $message->xBarycenter,
			yBarycentric: $message->yBarycenter,
			tax: 5,
			name: $message->name,
			points: $message->points,
			population: 0,
			prime: null !== $faction,
		);

		$this->sectorRepository->save($sector);

		$this->galaxyGenerationLogger->info('Sector generated successfully', [
			'identifier' => $sector->identifier,
		]);

		$this->generateSystems($sector, $message->danger);
	}

	/**
	 * Génère un ensemble de points aléatoires à l'intérieur du polygone du secteur,
	 * en appliquant un espacement minimal entre points.
	 *
	 * Algorithme:
	 * - Calcul de la boîte englobante du polygone
	 * - Rejection sampling: on tire un point aléatoire dans la bbox, on le garde s'il est dans le polygone
	 * - Filtre d'espacement: on impose une distance minimale entre points
	 *
	 * Paramétrage:
	 * - $baseCount: quantité nominale de points (ajustée par le danger)
	 * - $minSpacing: espacement minimal (peut varier selon l'échelle du polygone)
	 * - $maxAttempts: limite de tentatives pour éviter les boucles longues
	 *
	 * Note:
	 * - Pour un échantillonnage plus “blue-noise”, remplacez la boucle par l’algorithme de Bridson (Poisson-disc sampling).
	 * Cette version reste simple et efficace pour beaucoup de cas.
	 */
	private function generateSystems(Sector $sector, int $sectorDanger): void
	{
		$vertices = $this->normalizeVertices($this->galaxyConfiguration->getSectorVertices($sector->identifier));

		if (count($vertices) < 3) {
			$this->galaxyGenerationLogger->error('Not enough sector vertices to generate systems', [
				'identifier' => $sector->identifier,
				'vertices' => $vertices,
			]);

			return;
		}

		[$minX, $minY, $maxX, $maxY] = $this->polygonBounds($vertices);

		// Paramètres simples: ajuster le nombre de points avec le "danger"
		$baseCount = 25; // valeur de base
		$targetCount = max(1, (int) round($baseCount * (1.0 + ($sectorDanger / 10.0))));
		$minSpacing = $this->suggestSpacing($vertices); // espacement minimal en fonction de l'échelle
		$minSpacing2 = $minSpacing * $minSpacing;

		$maxAttempts = $targetCount * 40;
		$points = [];

		$this->galaxyGenerationLogger->debug('Looking for points vertices', [
			'minX' => $minX,
			'minY' => $minY,
			'maxX' => $maxX,
			'maxY' => $maxY,
			'minSpacing' => $minSpacing,
			'targetCount' => $targetCount,
			'maxAttempts' => $maxAttempts,
		]);

		$attempts = 0;
		while (count($points) < $targetCount && $attempts < $maxAttempts) {
			++$attempts;

			$x = $this->randFloat($minX, $maxX);
			$y = $this->randFloat($minY, $maxY);

			if (!$this->isPointInPolygon($x, $y, $vertices)) {
				continue;
			}

			// Test d'espacement minimal
			$ok = true;
			foreach ($points as [$px, $py]) {
				if ($this->dist2($x, $y, $px, $py) < $minSpacing2) {
					$ok = false;
					break;
				}
			}

			if ($ok) {
				$this->galaxyGenerationLogger->debug('System point generated', [
					'x' => $x,
					'y' => $y,
				]);

				$points[] = [$x, $y];
			}
		}

		foreach ($points as [$sx, $sy]) {
			$this->messageBus->dispatch(new SystemGenerationMessage(
				sectorIdentifier: $sector->identifier,
				sectorDanger: $sectorDanger,
				xPosition: intval(round($sx)),
				yPosition: intval(round($sy)),
				typeOfSystem: ($this->getProportion)(
					$this->galaxyConfiguration->galaxy['systemProportion'],
					random_int(1, 100),
				),
			));
		}
	}

	/**
	 * Normalise les vertices en une liste de [float x, float y].
	 * Accepte divers formats: ['x'=>..,'y'=>..], [x, y], "x,y".
	 *
	 * @return list<array{0: float, 1: float}>
	 */
	private function normalizeVertices(array $raw): array
	{
		return array_chunk($raw, 2);
	}

	/**
	 * Calcule la bbox [minX, minY, maxX, maxY].
	 *
	 * @param list<array{0: float, 1: float, 2: float, 3: float} $vertices
	 *
	 * @return array{0: float, 1: float, 2: float, 3: float}
	 */
	private function polygonBounds(array $vertices): array
	{
		$minX = $maxX = $vertices[0][0];
		$minY = $maxY = $vertices[0][1];

		foreach ($vertices as [$x, $y]) {
			if ($x < $minX) {
				$minX = $x;
			}
			if ($x > $maxX) {
				$maxX = $x;
			}
			if ($y < $minY) {
				$minY = $y;
			}
			if ($y > $maxY) {
				$maxY = $y;
			}
		}

		return [$minX, $minY, $maxX, $maxY];
	}

	/**
	 * Test point-dans-polygone (ray casting).
	 *
	 * @param list<array{0: float, 1: float}> $vertices
	 */
	private function isPointInPolygon(float $x, float $y, array $vertices): bool
	{
		$inside = false;
		$n = count($vertices);

		for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
			[$xi, $yi] = $vertices[$i];
			[$xj, $yj] = $vertices[$j];

			$intersect = (($yi > $y) !== ($yj > $y))
				&& ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

			if ($intersect) {
				$inside = !$inside;
			}
		}

		return $inside;
	}

	private function randFloat(float $min, float $max): float
	{
		return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
	}

	private function dist2(float $x1, float $y1, float $x2, float $y2): float
	{
		$dx = $x1 - $x2;
		$dy = $y1 - $y2;

		return $dx * $dx + $dy * $dy;
	}

	/**
	 * Propose un espacement minimal en fonction de l'échelle du polygone.
	 * Ici: ~2% de la plus grande dimension de la bbox, borné.
	 *
	 * @param list<array{0: float, 1: float}> $vertices
	 */
	private function suggestSpacing(array $vertices): float
	{
		[$minX, $minY, $maxX, $maxY] = $this->polygonBounds($vertices);

		$size = max($maxX - $minX, $maxY - $minY);
		$spacing = max(2.0, 0.02 * $size);

		return $spacing;
	}
}
