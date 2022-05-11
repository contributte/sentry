<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Security\Identity;
use Nette\Security\IUserStorage;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\UserDataBag;

class NetteSecurityIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event): ?Event
	{
		/** @var IUserStorage|null $storage */
		$storage = $this->context->getByType(IUserStorage::class, false);

		// There is no user storage
		if ($storage === null) {
			return $event;
		}

		/** @var Identity|null $identity */
		$identity = $storage->getIdentity();

		// Anonymous user
		if ($identity === null) {
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
