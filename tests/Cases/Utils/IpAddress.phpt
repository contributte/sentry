<?php declare(strict_types = 1);

use Contributte\Sentry\Utils\IpAddress;
use Contributte\Tester\Toolkit;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// IPv4
Toolkit::test(function (): void {
	Assert::same('192.168.122.0', IpAddress::anonymize('192.168.122.1'));
	Assert::same('10.0.0.0', IpAddress::anonymize('10.0.0.255'));
	Assert::same('0.0.0.0', IpAddress::anonymize('0.0.0.0'));
	Assert::same('255.255.255.0', IpAddress::anonymize('255.255.255.255'));
});

// IPv6
Toolkit::test(function (): void {
	Assert::same('2001:db8:85a3::', IpAddress::anonymize('2001:db8:85a3:8d3:1319:8a2e:370:7348'));
	Assert::same('2001:db8:85a3::', IpAddress::anonymize('[2001:db8:85a3::1]'));
	Assert::same('::', IpAddress::anonymize('::1'));
	Assert::same('fe80::', IpAddress::anonymize('fe80::1ff:fe23:4567:890a'));
});

// IPv4-mapped IPv6
Toolkit::test(function (): void {
	Assert::same('192.168.122.0', IpAddress::anonymize('::ffff:192.168.122.1'));
	Assert::same('192.168.122.0', IpAddress::anonymize('::ffff:c0a8:7a01'));
});

// Invalid
Toolkit::test(function (): void {
	Assert::null(IpAddress::anonymize(''));
	Assert::null(IpAddress::anonymize('foo'));
	Assert::null(IpAddress::anonymize('192.168.122'));
	Assert::null(IpAddress::anonymize('256.0.0.1'));
	Assert::null(IpAddress::anonymize('2001:db8::g'));
	Assert::null(IpAddress::anonymize('fe80::1%eth0'));
});
