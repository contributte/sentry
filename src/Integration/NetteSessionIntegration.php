<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Nette\DI\Container;
use Nette\Http\Session;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;

class NetteSessionIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		/** @var Session|null $session */
		$session = $this->context->getByType(Session::class, false);

		// There is no session
		if ($session === null) {
			return $event;
		}

		/** @var array<mixed, string> $iterator */
		$iterator = $session->getIterator();
		$data = [];

		foreach ($iterator as $section) {
			$data[$section] = iterator_to_array($session->getSection($section)->getIterator());
		}

		$event->setBreadcrumb(
			array_merge(
				$event->getBreadcrumbs(),
				[
					new Breadcrumb(
						Breadcrumb::LEVEL_INFO,
						Breadcrumb::TYPE_HTTP,
						'nette_session',
						'Nette Session',
						$data
					),
				]
			)
		);

		if (PHP_SAPI !== 'cli') {
			$event->setTags([
				'phpsessid' => $session->getId(),
			]);
		}

		return $event;
	}

}
