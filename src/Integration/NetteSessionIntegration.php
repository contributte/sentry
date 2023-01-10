<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use ArrayIterator;
use Nette\DI\Container;
use Nette\Http\Session;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;

class NetteSessionIntegration extends BaseIntegration
{

	public function __construct(protected Container $context)
	{
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		$session = $this->context->getByType(Session::class, false);

		// There is no session
		if (!$session instanceof Session) {
			return $event;
		}

		// @see https://github.com/nette/http/blob/v3.1/src/Http/Session.php
		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		$sessionData = $_SESSION['__NF']['DATA'] ?? [];

		/** @var array<mixed, string> $iterator */
		$iterator = new ArrayIterator(array_keys($sessionData));
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
				$session->getName() => $session->getId(),
			]);
		}

		return $event;
	}

}
