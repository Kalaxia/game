<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Enum;

enum NewsType: string
{
	case PutschAttempt = 'putsch_attempt';
	case NewCandidate = 'new_candidate';
	case NewLeader = 'new_leader';
	case FiredMinister = 'fired_minister';
	case NewMinister = 'new_minister';
	case ForeignNewCandidate = 'foreign_new_candidate';
	case ForeignPutschAttempt = 'foreign_putsch_attempt';
	case ForeignNewLeader = 'foreign_new_leader';
	case ForeignFiredMinister = 'foreign_fired_minister';
	case ForeignNewMinister = 'foreign_new_minister';
	case SectorWon = 'sector_won';
	case SectorLost = 'sector_lost';
	case SectorOwnerChange = 'sector_owner_change';
}
