<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use Nette\DI\Compiler;
use Nette\DI\Container as NetteContainer;
use Nette\DI\ContainerLoader;

final class Container
{

	/** @var callable[] */
	private array $onCompile = [];

	public function __construct(private string $key)
	{
	}

	public static function of(?string $key = null): Container
	{
		return new self($key ?? uniqid(random_bytes(16)));
	}

	public function withCompiler(callable $cb): Container
	{
		$this->onCompile[] = static function (Compiler $compiler) use ($cb): void {
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
