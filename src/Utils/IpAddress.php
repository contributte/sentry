<?php declare(strict_types = 1);

namespace Contributte\Sentry\Utils;

class IpAddress
{

	private const IPV4_MAPPED_PREFIX = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";

	/**
	 * Anonymize IP address by zeroing its host part.
	 * IPv4 keeps /24 (last octet is zeroed), IPv6 keeps /48 (last 80 bits are zeroed),
	 * IPv4-mapped IPv6 address is anonymized as IPv4 address.
	 *
	 * @return string|null Anonymized IP address or null if input is not a valid IP address
	 */
	public static function anonymize(string $ipAddress): ?string
	{
		// Strip brackets from IPv6 addresses (e.g. [::1] -> ::1)
		if (str_starts_with($ipAddress, '[') && str_ends_with($ipAddress, ']')) {
			$ipAddress = substr($ipAddress, 1, -1);
		}

		if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
			return null;
		}

		$packed = inet_pton($ipAddress);

		if ($packed === false) {
			return null;
		}

		// IPv4-mapped IPv6 address (e.g. ::ffff:192.168.122.1)
		if (strlen($packed) === 16 && str_starts_with($packed, self::IPV4_MAPPED_PREFIX)) {
			$packed = substr($packed, 12);
		}

		$mask = strlen($packed) === 4
			? str_repeat("\xff", 3) . "\x00"
			: str_repeat("\xff", 6) . str_repeat("\x00", 10);

		$anonymized = inet_ntop($packed & $mask);

		return $anonymized === false ? null : $anonymized;
	}

}
