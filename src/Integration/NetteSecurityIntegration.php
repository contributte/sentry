<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Contributte\Sentry\Utils\IpAddress;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Security\IIdentity;
use Nette\Security\UserStorage;
use Sentry\DataCollection\DataCollectionPolicy;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;
use Sentry\UserDataBag;

class NetteSecurityIntegration extends BaseIntegration
{

	/**
	 * @param bool $anonymizeIp Send anonymized IP address (IPv4 /24, IPv6 /48) instead of the full one,
	 *                          even if the SDK does not allow to collect user info
	 */
	public function __construct(
		protected Container $context,
		protected bool $anonymizeIp = false,
	)
	{
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		$storage = $this->context->getByType(UserStorage::class, false);

		// There is no user storage
		if (!$storage instanceof UserStorage) {
			return $event;
		}

		$session = $this->context->getByType(Session::class, false);

		// There is no session
		if (!$session instanceof Session) {
			return $event;
		}

		// Closed session
		if (!$session->isStarted()) {
			return $event;
		}

		$state = $storage->getState();

		// There is no user logged in
		if (!$state[0]) {
			return $event;
		}

		$identity = $state[1];

		// Anonymous user
		if (!($identity instanceof IIdentity)) {
			return $event;
		}

		$collectUserInfo = $this->shouldCollectUserInfo($hub);

		/** @var array<string, mixed> $identityData */
		$identityData = $identity->getData();
		$identityRoles = $identity->getRoles();
		$identityId = $identity->getId();

		$bag = new UserDataBag(
			is_scalar($identityId) ? (string) $identityId : '',
			$collectUserInfo && is_string($identityData['email'] ?? null) ? $identityData['email'] : null,
			$this->getIpAddress($collectUserInfo),
			is_string($identityData['username'] ?? null) ? $identityData['username'] : null
		);

		$bag->setMetadata('Roles', implode(',', $identityRoles));

		if ($collectUserInfo) {
			$bag->setMetadata('Identity', $identityData);
		}

		$event->setUser($bag);

		return $event;
	}

	protected function shouldCollectUserInfo(HubInterface $hub): bool
	{
		// sentry/sentry 4.38+ (send_default_pii and data_collection options)
		if (class_exists(DataCollectionPolicy::class)) {
			return DataCollectionPolicy::fromHub($hub)->shouldCollectUserInfo();
		}

		return $hub->getClient()?->getOptions()->shouldSendDefaultPii() ?? false;
	}

	protected function getIpAddress(bool $collectUserInfo): ?string
	{
		if (!$collectUserInfo && !$this->anonymizeIp) {
			return null;
		}

		$httpRequest = $this->context->getByType(IRequest::class, false);
		$ipAddress = $httpRequest instanceof IRequest ? $httpRequest->getRemoteAddress() : null;

		if ($ipAddress === null) {
			return null;
		}

		return $this->anonymizeIp ? IpAddress::anonymize($ipAddress) : $ipAddress;
	}

}
