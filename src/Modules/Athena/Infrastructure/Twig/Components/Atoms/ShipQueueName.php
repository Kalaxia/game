<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Atoms;

use App\Modules\Athena\Model\ShipQueue;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ShipQueueName', template: 'components/Atoms/Ship/ShipQueueName.html.twig')]
final class ShipQueueName
{
	public ShipQueue $queue;
}
