<?php declare(strict_types = 1);

use Contributte\Sentry\Integration\IgnoreErrorIntegration;
use Contributte\Tester\Toolkit;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Tester\Assert;
use function Sentry\withScope;

require_once __DIR__ . '/../../../bootstrap.php';

class GoodException extends RuntimeException
{
}

class BadException extends RuntimeException
{
}

// not matched exception and matched event
Toolkit::test(function (): void {
	$integration = new IgnoreErrorIntegration([
		'ignore_exception_instance' => [
			GoodException::class,
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
	$event->setExceptions([new ExceptionDataBag(new GoodException('bar foo'))]);

	withScope(function (Scope $scope) use ($event): void {
		$event = $scope->applyToEvent($event);
		Assert::null($event);
	});
});

Toolkit::test(function (): void {
	$integration = new IgnoreErrorIntegration([
		'ignore_exception_instance' => [
			GoodException::class,
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
	$event->setExceptions([new ExceptionDataBag(new BadException('bar foo'))]);

	withScope(function (Scope $scope) use ($event): void {
		$event = $scope->applyToEvent($event);
		Assert::notNull($event);
	});
});
