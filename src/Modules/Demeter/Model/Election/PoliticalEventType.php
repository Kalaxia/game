<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

enum PoliticalEventType: string
{
	case DemocraticElection = 'democratic_election';
	case Putsch = 'putsch';
	case DivineDesignation = 'divine_designation';
}
