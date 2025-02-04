<?php declare(strict_types = 1);

namespace Contributte\Sentry\Utils;

final class Regex
{

	/**
	 * @param 0|256|512|768 $flags
	 * @return array<array<int, int|string|null>|string|null>|null
	 */
	public static function match(string $subject, string $pattern, int $flags = 0): ?array
	{
		$ret = preg_match($pattern, $subject, $m, $flags);

		return $ret === 1 ? $m : null;
	}

	/**
	 * @return array<mixed>|null
	 */
	public static function matchAll(string $subject, string $pattern, int $flags = 0): ?array
	{
		$ret = preg_match_all($pattern, $subject, $m, $flags);

		return $ret !== false ? $m : null;
	}

}
