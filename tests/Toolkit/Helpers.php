<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\Neon\Neon;

final class Helpers
{

	/**
	 * @return mixed[]
	 */
	public static function neon(string $str): array
	{
		return (new NeonAdapter())->process((array) Neon::decode($str));
	}

}
