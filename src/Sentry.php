<?php declare(strict_types = 1);

namespace Contributte\Sentry;

use Contributte\Sentry\Exception\LogicalException;
use Sentry\ClientBuilder;
use Sentry\SentrySdk;
use Sentry\State\Hub;

class Sentry
{

	/**
	 * @param array{client?: array<string, mixed>} $config
	 */
	public static function register(array $config): void
	{
		if (!isset($config['client']) || $config['client'] === []) {
			throw new LogicalException('Missing Sentry client config');
		}

		if (($config['client']['dsn'] ?? null) === null) {
			throw new LogicalException('Missing Sentry DSN config');
		}

		$builder = ClientBuilder::create($config['client']);
		$hub = new Hub($builder->getClient());

		// Update Sentry Hub (static singleton)
		SentrySdk::setCurrentHub($hub);
	}

}
