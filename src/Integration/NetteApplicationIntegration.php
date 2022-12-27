<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\Application\Application;
use Nette\Application\Request;
use Nette\DI\Container;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;

class NetteApplicationIntegration extends BaseIntegration
{

	public function __construct(protected Container $context)
	{
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		$application = $this->context->getByType(Application::class, false);

		// There is no application
		if (!$application instanceof Application) {
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
							sprintf('Nette Application Request #%s', (int) $n + 1),
							$data
						),
					]
				)
			);
		}

		return $event;
	}

}
