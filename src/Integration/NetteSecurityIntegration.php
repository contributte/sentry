<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Security\IIdentity;
use Nette\Security\UserStorage;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;
use Sentry\UserDataBag;

class NetteSecurityIntegration extends BaseIntegration
{

	public function __construct(protected Container $context)
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

		$httpRequest = $this->context->getByType(IRequest::class);

		/** @var array<string, mixed> $identityData */
		$identityData = $identity->getData();
		$identityRoles = $identity->getRoles();
		$identityId = $identity->getId();

		$bag = new UserDataBag(
			is_scalar($identityId) ? (string) $identityId : '',
			is_string($identityData['email'] ?? null) ? $identityData['email'] : null,
			$httpRequest->getRemoteAddress(),
			is_string($identityData['username'] ?? null) ? $identityData['username'] : null
		);

		$bag->setMetadata('Roles', implode(',', $identityRoles));
		$bag->setMetadata('Identity', $identityData);

		$event->setUser($bag);

		return $event;
	}

}
