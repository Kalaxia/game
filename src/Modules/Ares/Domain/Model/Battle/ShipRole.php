<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * Behaviour of a squadron in battle (see docs/fleet-combat/ship-stats.md).
 */
enum ShipRole: string
{
	/** Hunts fighters and bombers, screens its fleet */
	case Interceptor = 'interceptor';
	/** Goes for heavy ships, avoids fighters */
	case Bomber = 'bomber';
	/** Anti-fighter screen staying close to the heavy ships */
	case Escort = 'escort';
	/** Durable brawler of the front line */
	case Line = 'line';
	/** Long range, stays behind the line */
	case Artillery = 'artillery';
	/** Heavy all-rounder, center of the formation */
	case Capital = 'capital';
}
