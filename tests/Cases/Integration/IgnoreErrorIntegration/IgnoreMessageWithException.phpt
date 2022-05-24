<?php declare(strict_types = 1);

use Contributte\Sentry\Integration\IgnoreErrorIntegration;
use Ninjify\Nunjuck\Toolkit;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Tester\Assert;
use function Sentry\withScope;

require_once __DIR__ . '/../../../bootstrap.php';

// not matched exception and matched event
Toolkit::test(function (): void {
	$integration = new IgnoreErrorIntegration([
		'ignore_exception_regex' => [
			'#foo bar#',
		],
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
	$event->setExceptions([new ExceptionDataBag(new RuntimeException('bar foo'))]);

	withScope(function (Scope $scope) use ($event): void {
		$event = $scope->applyToEvent($event);
		Assert::null($event);
	});
});
