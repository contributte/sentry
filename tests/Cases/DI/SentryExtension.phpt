<?php declare(strict_types = 1);

use Contributte\Sentry\DI\SentryExtension;
use Contributte\Sentry\Exception\LogicalException;
use Contributte\Sentry\Tracy\MultiLogger;
use Nette\DI\Compiler;
use Ninjify\Nunjuck\Toolkit;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Tester\Assert;
use Tester\Environment;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;
use Tracy\Bridges\Nette\TracyExtension;
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
	$container = Container::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Helpers::neon('
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
		$container = Container::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('tracy', new TracyExtension());
				$compiler->addExtension('sentry', new SentryExtension());
				$compiler->addConfig(Helpers::neon('
				sentry:
					enable: true
			'));
			})
			->build();

		call_user_func([$container, 'initialize']);
	}, LogicalException::class, 'Missing Sentry DSN config');
});

// No integrations
Toolkit::test(function (): void {
	$container = Container::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension());
			$compiler->addConfig(Helpers::neon('
				sentry:
					enable: true
					integrations: false

					client:
						dsn: "https://fakefakefake@fakefake.ingest.sentry.io/12345678"
			'));
		})
		->build();

	call_user_func([$container, 'initialize']);

	Assert::count(0, SentrySdk::getCurrentHub()->getClient()->getOptions()->getIntegrations());
});
