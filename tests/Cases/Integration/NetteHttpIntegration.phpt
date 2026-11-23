<?php declare(strict_types = 1);

use Contributte\Sentry\Integration\NetteHttpIntegration;
use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Sentry\ClientBuilder;
use Sentry\DataCollection\DataCollectionOptions;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\Hub;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

/**
 * @param array<string, mixed> $clientOptions
 * @param array{pii_sanitize_headers?: string[]} $integrationOptions
 * @return array<string, mixed>
 */
function processRequest(array $clientOptions, array $integrationOptions = []): array
{
	$rawBody = fn (): string => '{"password":"pw"}';
	$httpRequest = new Request(
		new UrlScript('https://example.com/foo?token=secret'),
		[],
		[],
		['PHPSESSID' => 'secret-session'],
		[
			'Authorization' => 'Bearer secret-token',
			'Cookie' => 'PHPSESSID=secret-session',
			'Content-Type' => 'application/json',
			'Content-Length' => '17',
			'X-Custom' => 'custom',
		],
		'POST',
		'192.168.122.1',
		null,
		$rawBody
	);

	$container = Mockery::mock(Container::class);
	$container->shouldReceive('getByType')
		->with(IRequest::class, false)
		->andReturn($httpRequest);

	$hub = new Hub(ClientBuilder::create($clientOptions + ['default_integrations' => false])->getClient());
	$event = Event::createEvent();
	$event->setRequest(['url' => 'https://example.com/replaced', 'data' => 'replaced']);

	$integration = new NetteHttpIntegration($container, $integrationOptions);
	$event = $integration->setup($hub, $event, EventHint::fromArray([]));
	Assert::type(Event::class, $event);

	return $event->getRequest();
}

// Default options (send_default_pii: false)
Toolkit::test(function (): void {
	$request = processRequest([]);

	Assert::same('https://example.com/foo?token=secret', $request['url']);
	Assert::same('POST', $request['method']);
	Assert::same(['[Filtered]'], $request['headers']['authorization']);
	Assert::same(['[Filtered]'], $request['headers']['cookie']);
	Assert::same(['custom'], $request['headers']['x-custom']);
	Assert::false(isset($request['cookies']));
	Assert::false(isset($request['env']));
});

// Request body is not collected when disabled
Toolkit::test(function (): void {
	$request = processRequest(['max_request_body_size' => 'never']);

	Assert::false(isset($request['data']));
});

// Request body is collected when allowed
Toolkit::test(function (): void {
	$request = processRequest(['max_request_body_size' => 'always']);

	Assert::same(['password' => 'pw'], $request['data']);
});

// PII allowed (send_default_pii: true)
Toolkit::test(function (): void {
	$request = processRequest(['send_default_pii' => true]);

	Assert::same(['Bearer secret-token'], $request['headers']['authorization']);
	Assert::same(['PHPSESSID=secret-session'], $request['headers']['cookie']);
	Assert::same(['PHPSESSID' => 'secret-session'], $request['cookies']);
	Assert::same(['REMOTE_ADDR' => '192.168.122.1'], $request['env']);
});

// Data collection option (sentry/sentry 4.38+)
Toolkit::test(function (): void {
	if (!class_exists(DataCollectionOptions::class)) {
		return;
	}

	$request = processRequest(['data_collection' => ['user_info' => false, 'cookies' => ['mode' => 'off'], 'http_bodies' => []]]);

	Assert::false(isset($request['cookies']));
	Assert::false(isset($request['data']));
	Assert::false(isset($request['env']));
	Assert::same(['[Filtered]'], $request['headers']['authorization']);
});

// Custom sanitized headers
Toolkit::test(function (): void {
	$request = processRequest([], ['pii_sanitize_headers' => ['X-Custom']]);

	Assert::same(['[Filtered]'], $request['headers']['x-custom']);
});

// No http request
Toolkit::test(function (): void {
	$container = Mockery::mock(Container::class);
	$container->shouldReceive('getByType')
		->with(IRequest::class, false)
		->andReturn(null);

	$hub = new Hub(ClientBuilder::create(['default_integrations' => false])->getClient());
	$event = Event::createEvent();

	$event = (new NetteHttpIntegration($container))->setup($hub, $event, EventHint::fromArray([]));
	Assert::type(Event::class, $event);
	Assert::same([], $event->getRequest());
});
