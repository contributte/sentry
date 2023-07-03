<?php declare(strict_types = 1);

namespace Contributte\Sentry\DI;

use Contributte\Sentry\Integration\ExtraIntegration;
use Contributte\Sentry\Integration\NetteApplicationIntegration;
use Contributte\Sentry\Integration\NetteHttpIntegration;
use Contributte\Sentry\Integration\NetteSecurityIntegration;
use Contributte\Sentry\Integration\NetteSessionIntegration;
use Contributte\Sentry\Sentry;
use Contributte\Sentry\Tracy\MultiLogger;
use Contributte\Sentry\Tracy\SentryLogger;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Sentry\Integration\EnvironmentIntegration;
use Sentry\Integration\ErrorListenerIntegration;
use Sentry\Integration\ExceptionListenerIntegration;
use Sentry\Integration\FatalErrorListenerIntegration;
use Sentry\Integration\FrameContextifierIntegration;
use Sentry\Integration\ModulesIntegration;
use Sentry\Integration\RequestIntegration;
use Sentry\Integration\TransactionIntegration;
use stdClass;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * @method stdClass getConfig()
 */
class SentryExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'enable' => Expect::bool()->default(false),
			'client' => Expect::array()->default([]),
			'integrations' => Expect::bool()->default(true),
			'logger' => Expect::structure([
				'captureMessages' => Expect::bool()->default(true),
				'captureLevels' => Expect::listOf('string')->default([
					ILogger::WARNING,
					ILogger::ERROR,
					ILogger::EXCEPTION,
					ILogger::CRITICAL,
				]),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$config = $this->getConfig();

		if ($config->enable === true) {
			$this->loadSentryConfiguration();
		}
	}

	public function beforeCompile(): void
	{
		$config = $this->getConfig();

		if ($config->enable === true) {
			$this->beforeSentryCompile();
		}
	}

	public function afterCompile(ClassType $class): void
	{
		$config = $this->getConfig();

		if ($config->enable === true) {
			$this->afterSentryCompile($class);
		}
	}

	private function loadSentryConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('multiLogger'))
			->setFactory(MultiLogger::class);

		$builder->addDefinition($this->prefix('sentryLogger'))
			->setFactory(SentryLogger::class)
			->addSetup('setCaptureMessages', [$config->logger->captureMessages])
			->addSetup('setCaptureLevels', [$config->logger->captureLevels])
			->setAutowired(false);
	}

	private function beforeSentryCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Disable autowiring for Tracy logger
		$builder->getDefinition('tracy.logger')->setAutowired(false);

		$def = $builder->getDefinition($this->prefix('multiLogger'));
		assert($def instanceof ServiceDefinition);
		$def->addSetup('addLogger', [$this->prefix('@sentryLogger')])
			->addSetup('addLogger', ['@tracy.logger']);
	}

	private function afterSentryCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		// Build config
		$config = $this->getConfig();
		$client = $config->client ?? [];

		if ($config->integrations) {
			$client['integrations'] = [
				// Sentry
				new Statement(EnvironmentIntegration::class),
				new Statement(ErrorListenerIntegration::class),
				new Statement(ExceptionListenerIntegration::class),
				new Statement(FatalErrorListenerIntegration::class),
				new Statement(FrameContextifierIntegration::class, [null]),
				new Statement(ModulesIntegration::class),
				new Statement(RequestIntegration::class, [null]),
				new Statement(TransactionIntegration::class),
				// Nette
				new Statement(NetteApplicationIntegration::class),
				new Statement(NetteHttpIntegration::class),
				new Statement(NetteSecurityIntegration::class),
				new Statement(NetteSessionIntegration::class),
				new Statement(ExtraIntegration::class, [[]]),
			];
		}

		$initialize = $class->getMethod('initialize');
		// @phpstan-ignore-next-line
		$initialize->addBody($builder->formatPhp(Sentry::class . '::register(?);', [['client' => $client]]));
		// @phpstan-ignore-next-line
		$initialize->addBody($builder->formatPhp(Debugger::class . '::setLogger(?);', [$builder->getDefinition($this->prefix('multiLogger'))]));
	}

}
