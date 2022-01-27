<?php declare(strict_types = 1);

namespace Contributte\Sentry\DI;

use Contributte\Sentry\Sentry;
use Contributte\Sentry\Tracy\MultiLogger;
use Contributte\Sentry\Tracy\SentryLogger;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * @method array getConfig()
 */
class SentryExtension24 extends CompilerExtension
{

	/** @var array<string, mixed> */
	private $defaults = [
		'enable' => false,
		'client' => [],
		'integrations' => true,
		'logger' => [
			'captureMessages' => true,
			'captureLevels' => [
				ILogger::WARNING,
				ILogger::ERROR,
				ILogger::EXCEPTION,
				ILogger::CRITICAL,
			],
		],
	];

	public function loadConfiguration(): void
	{
		$config = $this->validateConfig($this->defaults);

		if ($config['enable'] === true) {
			$this->loadSentryConfiguration();
		}
	}

	public function beforeCompile(): void
	{
		$config = $this->getConfig();

		if ($config['enable'] === true) {
			$this->beforeSentryCompile();
		}
	}

	public function afterCompile(ClassType $class): void
	{
		$config = $this->getConfig();

		if ($config['enable'] === true) {
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
			->addSetup('setCaptureMessages', [$config['logger']['captureMessages']])
			->addSetup('setCaptureLevels', [$config['logger']['captureLevels']])
			->setAutowired(false);
	}

	private function beforeSentryCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Disable autowiring for Tracy logger
		$builder->getDefinition('tracy.logger')->setAutowired(false);

		$builder->getDefinition($this->prefix('multiLogger'))
			->addSetup('addLogger', [$this->prefix('@sentryLogger')])
			->addSetup('addLogger', ['@tracy.logger']);
	}

	private function afterSentryCompile(ClassType $class): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		$initialize = $class->getMethod('initialize');
		$initialize->addBody($builder->formatPhp(Sentry::class . '::register(?);', ['client' => $config['client'] ?? []]));
		$initialize->addBody($builder->formatPhp(Debugger::class . '::setLogger(?);', [$this->prefix('@multiLogger')]));
	}

}
