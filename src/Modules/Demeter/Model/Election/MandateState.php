<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Election;

enum MandateState: string
{
	case Active = 'active';
	case DemocraticCampaign = 'democratic_campaign';
	case DemocraticVote = 'democratic_vote';
	case TheocraticCampaign = 'theocratic_campaign';
	case Putsch = 'putsch';
}
