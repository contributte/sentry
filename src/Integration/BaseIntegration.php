<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

abstract class BaseIntegration implements IntegrationInterface
{

	abstract public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event;

	public function setupOnce(): void
	{
		Scope::addGlobalEventProcessor(function (Event $event, EventHint $hint): ?Event {
			$hub = SentrySdk::getCurrentHub();
			$integration = $hub->getIntegration(static::class);

			// The integration could be bound to a client that is not the one
			// attached to the current hub. If this is the case, bail out
			if ($integration !== null) {
				return $this->setup($hub, $event, $hint);
			}

			return $event;
		});
	}

}
