<?php declare(strict_types = 1);

use Contributte\Sentry\Integration\IgnoreErrorIntegration;
use Ninjify\Nunjuck\Toolkit;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Tester\Assert;
use function Sentry\withScope;

require_once __DIR__ . '/../../../bootstrap.php';

// Ignore message
Toolkit::test(function (): void {
	$integration = new IgnoreErrorIntegration([
		'ignore_message_regex' => [
			'#foo bar#',
		],
	]);
	$integration->setupOnce();

	$client = Mockery::mock(ClientInterface::class);
	$client->shouldReceive('getIntegration')
		->once()
		->andReturn($integration);

	SentrySdk::getCurrentHub()->bindClient($client);

	$event = Event::createEvent();
	$event->setMessage('foo bar');

	withScope(function (Scope $scope) use ($event): void {
		$event = $scope->applyToEvent($event);
		Assert::null($event);
	});
});
