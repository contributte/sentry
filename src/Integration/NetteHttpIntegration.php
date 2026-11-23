<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use ReflectionClass;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\RequestIntegration;
use Sentry\State\HubInterface;

/**
 * Attaches Nette HTTP request to the event. The request data are collected
 * by the Sentry SDK RequestIntegration, so SDK options like send_default_pii,
 * max_request_body_size, data_collection or pii_sanitize_headers are respected.
 */
class NetteHttpIntegration extends BaseIntegration
{

	/**
	 * @param array{pii_sanitize_headers?: string[]} $options RequestIntegration options
	 */
	public function __construct(
		protected Container $context,
		protected array $options = [],
	)
	{
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		$httpRequest = $this->context->getByType(IRequest::class, false);

		// There is no http request
		if (!$httpRequest instanceof IRequest) {
			return $event;
		}

		$client = $hub->getClient();

		// RequestIntegration exposes its processing only via a global event processor,
		// which is bound to the RequestIntegration instance registered in the client
		$integrationClass = new ReflectionClass(RequestIntegration::class);

		// There is no client or RequestIntegration has changed its internals
		if ($client === null || !$integrationClass->hasMethod('processEvent')) {
			return $event;
		}

		$integration = new RequestIntegration(new NetteRequestFetcher($httpRequest), $this->options);

		// Nette request takes precedence over request collected by other integrations
		$event->setRequest([]);
		$integrationClass->getMethod('processEvent')->invoke($integration, $event, $client->getOptions());

		return $event;
	}

}
