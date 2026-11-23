<?php declare(strict_types = 1);

use Contributte\Sentry\Integration\NetteSecurityIntegration;
use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Security\SimpleIdentity;
use Nette\Security\UserStorage;
use Sentry\ClientBuilder;
use Sentry\DataCollection\DataCollectionOptions;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\Hub;
use Sentry\UserDataBag;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

/**
 * @param array<string, mixed> $clientOptions
 */
function processUser(array $clientOptions, bool $anonymizeIp = false, string $remoteAddress = '192.168.122.1'): UserDataBag
{
	$identity = new SimpleIdentity(42, ['admin', 'user'], [
		'username' => 'john',
		'email' => 'john@example.com',
		'phone' => '+420123456789',
	]);

	$storage = Mockery::mock(UserStorage::class);
	$storage->shouldReceive('getState')
		->andReturn([true, $identity, null]);

	$session = Mockery::mock(Session::class);
	$session->shouldReceive('isStarted')
		->andReturn(true);

	$httpRequest = new Request(new UrlScript('https://example.com/'), remoteAddress: $remoteAddress);

	$container = Mockery::mock(Container::class);
	$container->shouldReceive('getByType')
		->with(UserStorage::class, false)
		->andReturn($storage);
	$container->shouldReceive('getByType')
		->with(Session::class, false)
		->andReturn($session);
	$container->shouldReceive('getByType')
		->with(IRequest::class, false)
		->andReturn($httpRequest);

	$hub = new Hub(ClientBuilder::create($clientOptions + ['default_integrations' => false])->getClient());

	$integration = new NetteSecurityIntegration($container, $anonymizeIp);
	$event = $integration->setup($hub, Event::createEvent(), EventHint::fromArray([]));
	Assert::type(Event::class, $event);

	$user = $event->getUser();
	Assert::type(UserDataBag::class, $user);

	return $user;
}

// Default options (send_default_pii: false)
Toolkit::test(function (): void {
	$user = processUser([]);

	Assert::same('42', $user->getId());
	Assert::same('john', $user->getUsername());
	Assert::null($user->getEmail());
	Assert::null($user->getIpAddress());
	Assert::same(['Roles' => 'admin,user'], $user->getMetadata());
});

// PII allowed (send_default_pii: true)
Toolkit::test(function (): void {
	$user = processUser(['send_default_pii' => true]);

	Assert::same('42', $user->getId());
	Assert::same('john', $user->getUsername());
	Assert::same('john@example.com', $user->getEmail());
	Assert::same('192.168.122.1', $user->getIpAddress());
	Assert::same('admin,user', $user->getMetadata()['Roles']);
	Assert::same([
		'username' => 'john',
		'email' => 'john@example.com',
		'phone' => '+420123456789',
	], $user->getMetadata()['Identity']);
});

// Anonymized IP address without PII
Toolkit::test(function (): void {
	$user = processUser([], true);

	Assert::null($user->getEmail());
	Assert::same('192.168.122.0', $user->getIpAddress());
	Assert::false(isset($user->getMetadata()['Identity']));
});

// Anonymized IP address with PII
Toolkit::test(function (): void {
	$user = processUser(['send_default_pii' => true], true, '2001:db8:85a3:8d3:1319:8a2e:370:7348');

	Assert::same('john@example.com', $user->getEmail());
	Assert::same('2001:db8:85a3::', $user->getIpAddress());
});

// Data collection option (sentry/sentry 4.38+)
Toolkit::test(function (): void {
	if (!class_exists(DataCollectionOptions::class)) {
		return;
	}

	$user = processUser(['send_default_pii' => true, 'data_collection' => ['user_info' => false]]);
	Assert::null($user->getEmail());
	Assert::null($user->getIpAddress());

	$user = processUser(['data_collection' => ['user_info' => true]]);
	Assert::same('john@example.com', $user->getEmail());
	Assert::same('192.168.122.1', $user->getIpAddress());
});
