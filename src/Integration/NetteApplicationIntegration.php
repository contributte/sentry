<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\Application\Application;
use Nette\Application\Request;
use Nette\DI\Container;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\State\HubInterface;

class NetteApplicationIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event): ?Event
	{
		/** @var Application|null $application */
		$application = $this->context->getByType(Application::class, false);

		// There is no application
		if ($application === null) {
			return $event;
		}

		foreach ($application->getRequests() as $n => $request) {
			$data = [
				'method' => $request->getMethod(),
				'presenter' => $request->getPresenterName(),
				'params' => $request->getParameters(),
			];

			if ($request->hasFlag(Request::VARYING)) {
				$data['flag'] = Request::VARYING;
			} elseif ($request->hasFlag(Request::RESTORED)) {
				$data['flag'] = Request::RESTORED;
			}

			$event->setBreadcrumb(
				array_merge(
					$event->getBreadcrumbs(),
					[
						new Breadcrumb(
							Breadcrumb::LEVEL_INFO,
							Breadcrumb::TYPE_HTTP,
							'nette_application_request',
							sprintf('Nette Application Request #%s', intval($n) + 1),
							$data
						),
					]
				)
			);
		}

		return $event;
	}

}
