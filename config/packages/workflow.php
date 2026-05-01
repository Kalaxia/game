<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;

return App::config([
	'framework' => [
		'workflows' => [
			'faction_mandate' => [
				'type' => 'state_machine',
				'marking_store' => [
					'type' => 'method',
					'property' => 'mandateState',
				],
				'supports' => [Color::class],
				'initial_marking' => MandateState::Active,
				'places' => MandateState::cases(),
				'transitions' => [
					// Democratic regimes
					'democratic_campaign' => [
						'from' => MandateState::Active,
						'to' => MandateState::DemocraticCampaign,
					],
					'democratic_vote' => [
						'from' => MandateState::DemocraticCampaign,
						'to' => MandateState::DemocraticVote,
					],
					'democratic_result' => [
						'from' => MandateState::DemocraticVote,
						'to' => MandateState::Active,
					],
					// Theocratic regimes
					'theocratic_campaign' => [
						'from' => MandateState::Active,
						'to' => MandateState::TheocraticCampaign,
					],
					'theocratic_designation' => [
						'from' => MandateState::TheocraticCampaign,
						'to' => MandateState::Active,
					],
					// Democratic and Theocratic regimes
					'missing_candidates' => [
						'from' => [MandateState::TheocraticCampaign, MandateState::DemocraticCampaign],
						'to' => MandateState::Active,
					],
					'unique_candidate' => [
						'from' => [MandateState::TheocraticCampaign, MandateState::DemocraticCampaign],
						'to' => MandateState::Active,
					],
					// Royalistic regimes
					'royalistic_putsch' => [
						'from' => MandateState::Active,
						'to' => MandateState::Putsch,
					],
					'royalistic_crowning' => [
						'from' => MandateState::Putsch,
						'to' => MandateState::Active,
					],
				],
			],
		],
	],
]);
