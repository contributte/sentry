<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use Sentry\Event;
use Sentry\State\HubInterface;

class NetteHttpIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event): ?Event
	{
		/** @var IRequest|null $httpRequest */
		$httpRequest = $this->context->getByType(IRequest::class);

		// There is no http request
		if ($httpRequest === null) {
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
