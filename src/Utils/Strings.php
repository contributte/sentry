<?php declare(strict_types = 1);

namespace Contributte\Sentry\Utils;

class Strings
{

	public static function environment(bool $debugMode): string
	{
		return $debugMode ? 'debug' : 'production';
	}

	public static function stringify(string|int|float|bool|null $str): string
	{
		if ($str === null) {
			return 'null';
		}

		if (is_bool($str)) {
			return $str === true ? 'yes' : 'no';
		}

		return strval($str);
	}

}
