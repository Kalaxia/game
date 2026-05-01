<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Story;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Infrastructure\DataFixtures\Factory\ConversationFactory;
use App\Modules\Hermes\Infrastructure\DataFixtures\Factory\ConversationUserFactory;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use App\Modules\Zeus\Model\Player;
use Zenstruck\Foundry\Story;

class DemocraticRegimeWorkflowStory extends Story
{
	public function build(): void
	{
		$faction = FactionFactory::createOne([
			'identifier' => ColorResource::KALANKAR,
			'regime' => Color::REGIME_DEMOCRATIC,
			'mandateState' => MandateState::Active,
		]);

		$factionPlayer = PlayerFactory::createOne([
			'faction' => $faction,
			'statement' => Player::DEAD,
		]);

		PlayerFactory::createMany(20, [
			'faction' => $faction,
			'status' => Player::STANDARD,
		]);

		PlayerFactory::createMany(10, [
			'faction' => $faction,
			'status' => Player::PARLIAMENT,
		]);

		$conversation = ConversationFactory::createOne([
			'title' => 'Communication de la Fédération',
			'type' => Conversation::TY_SYSTEM,
		]);

		ConversationUserFactory::createOne([
			'player' => $factionPlayer,
			'conversation' => $conversation,
		]);
	}
}
