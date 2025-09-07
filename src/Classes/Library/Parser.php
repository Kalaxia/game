<?php

namespace App\Classes\Library;

use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;

class Parser
{
	public bool $parseIcon = true;
	public bool $parseLink = true;
	public bool $parseSmile = true;

	public bool $parsePlayer = true;
	public bool $parsePlace = true;

	public bool $parseTag = true;
	public bool $parseBigTag = false;

	protected PlaceManager $placeManager;

	public function __construct(
		private readonly PlayerRepositoryInterface $playerRepository,
		private readonly PlaceRepositoryInterface $placeRepository,
		private readonly UrlGeneratorInterface $urlGenerator,
		private readonly string $mediaPath,
	) {
	}

	#[Required]
	public function setPlaceManager(PlaceManager $placeManager): void
	{
		$this->placeManager = $placeManager;
	}

	public function parse(string $string): string
	{
		$string = static::protect($string);

		if ($this->parseLink) {
			$string = $this->parseLink($string);
		}
		if ($this->parseIcon) {
			$string = $this->parseIcon($string);
		}
		if ($this->parseSmile) {
			$string = $this->parseSmile($string);
		}
		if ($this->parsePlayer) {
			$string = $this->parsePlayer($string);
		}
		if ($this->parsePlace) {
			$string = $this->parsePlace($string);
		}
		if ($this->parseTag) {
			$string = $this->parseTag($string);
		}
		if ($this->parseBigTag) {
			$string = $this->parseBigTag($string);
		}

		return $string;
	}

	public static function protect(string $string): string
	{
		$string = trim($string);
		$string = htmlspecialchars($string);
		$string = nl2br($string);

		return $string;
	}

	/** @TODO Refactor this inferno */
	public function getToolbar(): string
	{
		$tl = '<div class="toolbar">';
		if ($this->parseTag) {
			$tl .= '<button data-tag="bl">Gras</button>';
			$tl .= '<button data-tag="it">Italique</button>';
		}
		if ($this->parseIcon) {
			//		$tl .= '<button data-tag="ic">Icône</button>';
		}
		if ($this->parsePlayer) {
			$tl .= '<button data-tag="py">Joueur</button>';
		}
		if ($this->parsePlace) {
			$tl .= '<button data-tag="pl">Planète</button>';
		}
		$tl .= '</div>';

		return $tl;
	}

	protected function parseIcon(string $string): string
	{
		$string = \preg_replace('#\[pa\]#', '<img src="'.$this->mediaPath.'resources/pa.png" alt="pa" class="hb lt icon-color" title="point d\'action" />', $string);
		$string = \preg_replace('#\[pev\]#', '<img src="'.$this->mediaPath.'resources/pev.png" alt="pev" class="hb lt icon-color" title="point équivalent vaisseaux" />', (string) $string);
		$string = \preg_replace('#\[credit\]#', '<img src="'.$this->mediaPath.'resources/credit.png" alt="credit" class="hb lt icon-color" title="crédit" />', (string) $string);
		$string = \preg_replace('#\[ressource\]#', '<img src="'.$this->mediaPath.'resources/resource.png" alt="resource" class="hb lt icon-color" title="ressource" />', (string) $string);
		$string = \preg_replace('#\[releve\]#', '<img src="'.$this->mediaPath.'resources/time.png" alt="time" class="hb lt icon-color" title="relève" />', (string) $string);

		$string = \preg_replace('#\[attaque\]#', '<img src="'.$this->mediaPath.'resources/attack.png" alt="attack" class="hb lt icon-color" title="point d\'attaque" />', (string) $string);
		$string = \preg_replace('#\[vie\]#', '<img src="'.$this->mediaPath.'resources/life.png" alt="life" class="hb lt icon-color" title="point de vie" />', (string) $string);
		$string = \preg_replace('#\[defense\]#', '<img src="'.$this->mediaPath.'resources/defense.png" alt="defense" class="hb lt icon-color" title="point de défense" />', (string) $string);
		$string = \preg_replace('#\[vitesse\]#', '<img src="'.$this->mediaPath.'resources/speed.png" alt="speed" class="hb lt icon-color" title="point de vitesse" />', (string) $string);

		return $string;
	}

	protected function parseLink(string $string): string
	{
		return \preg_replace_callback(
			"/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/",
			function ($m) {
				$url = $m[0];
				$str = $url;
				$str = preg_replace('#^https?://#', '', $str);
				$str = strlen($str) > 32 ? substr($str, 0, 32).'...' : $str;

				return '<a href="'.$url.'" target="_blank">'.$str.'</a>';
			},
			$string
		);
	}

	protected function parseSmile(string $string): string
	{
		return $string;
	}

	protected function parsePlayer(string $string): string
	{
		return preg_replace_callback(
			'#\[\@(.+)\]#isU',
			fn($m) => (($player = $this->playerRepository->getByName($m[1])) !== null)
					? '<a href="' . $this->urlGenerator->generate('embassy', ['player' => $player->id]) . '" class="color' . $player->faction->identifier . ' hb lt" title="voir le profil">' . $player->name . '</a>'
					: $m[0],
			$string
		);
	}

	protected function parsePlace($string)
	{
		return \preg_replace_callback(
			'#\[\#(.+)\]#isU',
			function ($m) {
				if (($place = $this->placeRepository->get($m[1]))) {
					if ($place->player !== null) {
						return '<a href="'.$this->urlGenerator->generate('map', ['place' => $place->id]).'" class="color'.$place->player->faction->identifier.' hb lt" title="voir la planète">'.$place->name.'</a>';
					} else {
						return '<a href="'.$this->urlGenerator->generate('map', ['place' => $place->id]).'" class="hb lt" title="voir la planète">planète rebelle</a>';
					}
				}

				return $m[0];
			},
			(string) $string
		);
	}

	protected function parseTag(string $string): string
	{
		$string = \preg_replace('#\[b\](.+)\[/b\]#isU', '<strong>$1</strong>', $string);
		$string = \preg_replace('#\[i\](.+)\[/i\]#isU', '<em>$1</em>', (string) $string);

		return $string;
	}

	protected function parseBigTag(string $string): string
	{
		return $string;
	}
}
