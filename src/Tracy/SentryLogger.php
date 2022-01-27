<?php declare(strict_types = 1);

namespace Contributte\Sentry\Tracy;

use Contributte\Sentry\Utils\Helpers;
use Sentry\SentrySdk;
use Throwable;
use Tracy\ILogger;

class SentryLogger implements ILogger
{

	/** @var bool */
	protected $captureMessages = true;

	/** @var string[] */
	protected $captureLevels = [
		self::WARNING,
		self::ERROR,
		self::EXCEPTION,
		self::CRITICAL,
	];

	public function setCaptureMessages(bool $capture): void
	{
		$this->captureMessages = $capture;
	}

	/**
	 * @param string[] $levels
	 */
	public function setCaptureLevels(array $levels): void
	{
		$this->captureLevels = $levels;
	}

	/**
	 * @param mixed $value
	 * @param mixed $level
	 */
	public function log($value, $level = self::INFO): void
	{
		if ($value instanceof Throwable) {
			SentrySdk::getCurrentHub()->captureException($value);
		} elseif (
			is_string($value)
			&& $this->captureMessages
			&& in_array($level, $this->captureLevels, true)
		) {
			SentrySdk::getCurrentHub()->captureMessage($value, Helpers::mapSeverity($level));
		}
	}

}
