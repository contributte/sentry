<?php declare(strict_types = 1);

namespace Contributte\Sentry;

use Contributte\Sentry\Exception\LogicalException;
use Sentry\ClientBuilder;
use Sentry\SentrySdk;
use Sentry\State\Hub;

class Sentry
{

	/**
	 * @param mixed[] $config
	 */
	public static function register(array $config): void
	{
		if (($config['client'] ?? []) === []) {
			throw new LogicalException('Missing client config');
		}

		$builder = ClientBuilder::create($config['client']);
		$hub = new Hub($builder->getClient());

		// Update Sentry Hub (static singleton)
		SentrySdk::setCurrentHub($hub);
	}

}
