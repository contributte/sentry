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
		return match ($level) {
			ILogger::DEBUG => Severity::debug(),
			ILogger::INFO => Severity::info(),
			ILogger::WARNING => Severity::warning(),
			ILogger::ERROR => Severity::error(),
			ILogger::EXCEPTION => Severity::fatal(),
			ILogger::CRITICAL => Severity::fatal(),
			default => Severity::info(),
		};
	}

}
