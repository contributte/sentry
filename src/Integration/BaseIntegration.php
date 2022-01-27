<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

abstract class BaseIntegration implements IntegrationInterface
{

	abstract public function setup(HubInterface $hub, Event $event): void;

	public function setupOnce(): void
	{
		Scope::addGlobalEventProcessor(function (Event $event): Event {
			$hub = SentrySdk::getCurrentHub();
			$integration = $hub->getIntegration(self::class);

			// The integration could be bound to a client that is not the one
			// attached to the current hub. If this is the case, bail out
			if ($integration !== null) {
				$this->setup($hub, $event);
			}

			return $event;
		});
	}

}
