<?php declare(strict_types = 1);

use Contributte\Sentry\DI\SentryExtension24;
use Contributte\Sentry\Exception\LogicalException;
use Contributte\Sentry\Tracy\MultiLogger;
use Nette\DI\Compiler;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tester\Environment;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\ILogger;

require_once __DIR__ . '/../../bootstrap.php';

if (class_exists('Nette\DI\Definitions\ServiceDefinition')) {
	Environment::skip('Require Nette 2.4');
}

// Basic
Toolkit::test(function (): void {
	$container = Container::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('sentry', new SentryExtension24());
			$compiler->addConfig(Helpers::neon('
				sentry:
					enable: true

					client:
						dns: https://fake1@fake2.ingest.sentry.io/fake3
			'));
		})
		->build();

	Assert::count(3, $container->findByType(ILogger::class));
	Assert::type(MultiLogger::class, $container->getByType(ILogger::class));
});

// No client setup
Toolkit::test(function (): void {
	Assert::exception(static function (): void {
		$container = Container::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('tracy', new TracyExtension());
				$compiler->addExtension('sentry', new SentryExtension24());
				$compiler->addConfig(Helpers::neon('
				sentry:
					enable: true
			'));
			})
			->build();

		call_user_func([$container, 'initialize']);
	}, LogicalException::class, 'Missing client config');
});
