<?php declare(strict_types = 1);

use Contributte\Sentry\Utils\Strings;
use Contributte\Tester\Toolkit;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Environment
Toolkit::test(function (): void {
	Assert::equal('debug', Strings::environment(true));
	Assert::equal('production', Strings::environment(false));
});

// Stringify
Toolkit::test(function (): void {
	Assert::equal('null', Strings::stringify(null));
	Assert::equal('yes', Strings::stringify(true));
	Assert::equal('no', Strings::stringify(false));
	Assert::equal('foo', Strings::stringify('foo'));
});
