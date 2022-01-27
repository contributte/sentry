<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\Session;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class NetteSessionIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event): void
	{
		$hub->configureScope(function (Scope $scope) use ($event): void {
			$session = $this->context->getByType(Session::class);
			$iterator = $session->getIterator();
			$data = [];

			foreach ($iterator as $section) {
				$data[(string) $section] = iterator_to_array($session->getSection($section)->getIterator());
			}

			$scope->addBreadcrumb(new Breadcrumb(
				Breadcrumb::LEVEL_INFO,
				Breadcrumb::TYPE_HTTP,
				'nette_session',
				'Nette Session',
				$data
			));

			if (PHP_SAPI !== 'cli') {
				$event->setTags([
					'phpsessid' => $session->getId(),
				]);
			}
		});
	}

}
