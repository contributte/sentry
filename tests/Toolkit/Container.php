<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Nette\DI\Compiler;
use Nette\DI\Container as NetteContainer;
use Nette\DI\ContainerLoader;

final class Container
{

	/** @var string */
	private $key;

	/** @var callable[] */
	private $onCompile = [];

	public function __construct(string $key)
	{
		$this->key = $key;
	}

	public static function of(?string $key = null): Container
	{
		return new static($key ?? uniqid(random_bytes(16)));
	}

	public function withCompiler(callable $cb): Container
	{
		$this->onCompile[] = function (Compiler $compiler) use ($cb): void {
			$cb($compiler);
		};

		return $this;
	}

	public function build(): NetteContainer
	{
		$loader = new ContainerLoader(TEMP_DIR, true);
		$class = $loader->load(function (Compiler $compiler): void {
			foreach ($this->onCompile as $cb) {
				$cb($compiler);
			}
		}, $this->key);

		return new $class();
	}

}
