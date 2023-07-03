<?php declare(strict_types = 1);

namespace Contributte\Sentry\Tracy;

use Tracy\ILogger;

class MultiLogger implements ILogger
{

	/** @var ILogger[] */
	private array $loggers = [];

	public function addLogger(ILogger $logger): void
	{
		$this->loggers[] = $logger;
	}

	/**
	 * @param mixed $value
	 * @param string $level
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function log($value, $level = self::INFO): void
	{
		foreach ($this->loggers as $logger) {
			$logger->log($value, $level);
		}
	}

}
