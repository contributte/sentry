<?php declare(strict_types = 1);

namespace Contributte\Sentry\DI;

use Contributte\Sentry\Sentry;
use Contributte\Sentry\Tracy\MultiLogger;
use Contributte\Sentry\Tracy\SentryLogger;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
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
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		$initialize = $class->getMethod('initialize');
		$initialize->addBody($builder->formatPhp(Sentry::class . '::register(?);', [['client' => $config->client ?? []]]));
		$initialize->addBody($builder->formatPhp(Debugger::class . '::setLogger(?);', [$this->prefix('@multiLogger')]));
	}

}
