<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Security\SimpleIdentity;
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

		$state = $storage->getState();

		// There is no user logged in
		if (!$state[0]) {
			return $event;
		}

		$identity = $state[1];

		// Anonymous user
		if (!$identity instanceof SimpleIdentity) {
			return $event;
		}

		$httpRequest = $this->context->getByType(IRequest::class);

		$bag = new UserDataBag(
			(string) $identity->getId(),
			$identity->getData()['email'] ?? null,
			$httpRequest->getRemoteAddress(),
			$identity->getData()['username'] ?? null
		);

		$bag->setMetadata('Roles', implode(',', $identity->getRoles()));
		$bag->setMetadata('Identity', $identity->getData());

		$event->setUser($bag);

		return $event;
	}

}
