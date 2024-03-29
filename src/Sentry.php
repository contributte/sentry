<?php declare(strict_types = 1);

namespace Contributte\Sentry;

use Contributte\Sentry\Exception\LogicalException;
use Psr\Log\LoggerInterface;
use Sentry\ClientBuilder;
use Sentry\SentrySdk;
use Sentry\Serializer\RepresentationSerializerInterface;
use Sentry\State\Hub;
use Sentry\Transport\TransportInterface;

class Sentry
{

	/**
	 * @param array{
	 *     client?: array<string, mixed>,
	 *     clientBuilder?: array{
	 *        representationSerializer?: RepresentationSerializerInterface,
	 *        logger?: LoggerInterface,
	 *        sdkIdentifier?: string,
	 *        sdkVersion?: string,
	 *        transport?: TransportInterface,
	 *     }
	 * } $config
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

		if (($config['clientBuilder']['representationSerializer'] ?? null) !== null) {
			$builder->setRepresentationSerializer($config['clientBuilder']['representationSerializer']);
		}

		if (($config['clientBuilder']['logger'] ?? null) !== null) {
			$builder->setLogger($config['clientBuilder']['logger']);
		}

		if (($config['clientBuilder']['sdkIdentifier'] ?? null) !== null) {
			$builder->setSdkIdentifier($config['clientBuilder']['sdkIdentifier']);
		}

		if (($config['clientBuilder']['sdkVersion'] ?? null) !== null) {
			$builder->setSdkVersion($config['clientBuilder']['sdkVersion']);
		}

		if (($config['clientBuilder']['transport'] ?? null) !== null) {
			$builder->setTransport($config['clientBuilder']['transport']);
		}

		$hub = new Hub($builder->getClient());

		// Update Sentry Hub (static singleton)
		SentrySdk::setCurrentHub($hub);
	}

}
