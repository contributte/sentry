<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;

class NetteHttpIntegration extends BaseIntegration
{

	public function __construct(protected Container $context)
	{
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		$httpRequest = $this->context->getByType(IRequest::class, false);

		// There is no http request
		if (!$httpRequest instanceof IRequest) {
			return $event;
		}

		$data = [
			'url' => $httpRequest->getUrl()->__toString(),
			'method' => $httpRequest->getMethod(),
		];

		if ($httpRequest->getUrl()->getQuery() !== '') {
			$data['query_string'] = $httpRequest->getUrl()->getQuery();
		}

		$data['cookies'] = $httpRequest->getCookies();
		$data['headers'] = $httpRequest->getHeaders();

		$body = $httpRequest->getRawBody();

		if ($body !== null && $body !== '') {
			$data['data'] = $body;
		}

		$event->setRequest($data);

		return $event;
	}

}
