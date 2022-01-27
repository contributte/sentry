<?php declare(strict_types = 1);

namespace Contributte\Sentry\Utils;

use Sentry\Severity;
use Tracy\ILogger;

class Helpers
{

	/**
	 * Map Tracy level to Sentry severity
	 */
	public static function mapSeverity(string $level): Severity
	{
		switch ($level) {
			case ILogger::DEBUG:
				return Severity::debug();

			case ILogger::INFO:
				return Severity::info();

			case ILogger::WARNING:
				return Severity::warning();

			case ILogger::ERROR:
				return Severity::error();

			case ILogger::EXCEPTION:
				return Severity::fatal();

			case ILogger::CRITICAL:
				return Severity::fatal();

			default:
				return Severity::info();
		}
	}

}
