<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\Application\Application;
use Nette\Application\Request;
use Nette\DI\Container;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class NetteApplicationIntegration extends BaseIntegration
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
			$app = $this->context->getByType(Application::class);

			// Get application requests
			if ($app->getRequests() === []) {
				return;
			}

			foreach ($app->getRequests() as $n => $request) {
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

				$scope->addBreadcrumb(new Breadcrumb(
					Breadcrumb::LEVEL_INFO,
					Breadcrumb::TYPE_HTTP,
					'nette_application_request',
					sprintf('Nette Application Request #%s', intval($n) + 1),
					$data
				));
			}
		});
	}

}
