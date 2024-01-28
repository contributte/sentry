<?php declare(strict_types = 1);

use Contributte\Sentry\DI\SentryExtension;
use Contributte\Sentry\Exception\LogicalException;
use Contributte\Sentry\Tracy\MultiLogger;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Sentry\Client;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Tester\Assert;
use Tester\Environment;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\Bridges\Psr\TracyToPsrLoggerAdapter;
use Tracy\ILogger;

require_once __DIR__ . '/../../bootstrap.php';

if (!class_exists('Nette\DI\Definitions\ServiceDefinition')) {
	Environment::skip('Require Nette 3');
}

Toolkit::setUp(function (): void {
	SentrySdk::setCurrentHub(Mockery::mock(HubInterface::class));
});

// Basic
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Neonkit::load('
				sentry:
					enable: true

					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);

	Assert::count(3, $container->findByType(ILogger::class));
	Assert::type(MultiLogger::class, $container->getByType(ILogger::class));
	Assert::count(13, SentrySdk::getCurrentHub()->getClient()->getOptions()->getIntegrations());
});

// No client setup
Toolkit::test(function (): void {
	Assert::exception(static function (): void {
		$container = ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('tracy', new TracyExtension());
				$compiler->addExtension('sentry', new SentryExtension());
				$compiler->addConfig(Neonkit::load('
				sentry:
					enable: true
			'));
			})
			->build();

		call_user_func([$container, 'initialize']);
	}, LogicalException::class, 'Missing Sentry DSN config');
});

// Default integrations
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Neonkit::load('
				sentry:
					enable: true
					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);

	Assert::count(13, SentrySdk::getCurrentHub()->getClient()->getOptions()->getIntegrations());
});

// Enable is string
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Neonkit::load('
				sentry:
					enable: "true"
					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);

	Assert::equal(12345678, SentrySdk::getCurrentHub()->getClient()->getOptions()->getDsn()->getProjectId());
});
// Enable is invalid
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('tracy', new TracyExtension());
					$compiler->addExtension('sentry', new SentryExtension());
					$compiler->addConfig(Neonkit::load('
				sentry:
					enable: []
			'));
				})
				->build();
		},
		InvalidConfigurationException::class,
		"The item 'sentry › enable' expects to be bool, array given."
	);
});

// No default integrations
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Neonkit::load('
				sentry:
					enable: true
					integrations: false

					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"
						integrations:
							- Contributte\Sentry\Integration\ExtraIntegration([
								version: 1.2.3
							])
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);

	Assert::count(1, SentrySdk::getCurrentHub()->getClient()->getOptions()->getIntegrations());
});

// Merge integrations
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Neonkit::load('
				sentry:
					enable: true

					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"
						integrations:
							- Contributte\Sentry\Integration\ExtraIntegration([
								version: 1.2.3
							])
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);

	Assert::count(14, SentrySdk::getCurrentHub()->getClient()->getOptions()->getIntegrations());
});

// Test client has custom logger
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Neonkit::load('
				services:
					customLogger: Tracy\Bridges\Psr\TracyToPsrLoggerAdapter()

				sentry:
					enable: true

					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"

					clientBuilder:
						logger: @customLogger
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);
	$client = SentrySdk::getCurrentHub()->getClient();
	Assert::notNull($client);
	$reflectionClass = new ReflectionClass(Client::class);
	$reflectionProperty = $reflectionClass->getProperty('logger');
	Assert::true($reflectionProperty->getValue($client) instanceof TracyToPsrLoggerAdapter);
});
