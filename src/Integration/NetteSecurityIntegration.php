<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Security\Identity;
use Nette\Security\IUserStorage;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\UserDataBag;

class NetteSecurityIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event): void
	{
		$hub->configureScope(function (Scope $scope): void {
			/** @var Identity|null $identity */
			$identity = $this->context->getByType(IUserStorage::class)->getIdentity();

			// User can be not logged in
			if ($identity === null) {
				return;
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

			$scope->setUser($bag);
		});
	}

}
