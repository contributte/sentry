<?php declare(strict_types = 1);

namespace Contributte\Sentry\Performance;

use Contributte\Sentry\Exception\LogicalException;
use Contributte\Sentry\Exception\Runtime\PerformanceException;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Http\IRequest;
use Sentry\SentrySdk;
use Sentry\Tracing\TransactionContext;

class NetteApplicationMonitor
{

	/** @var IRequest */
	private $request;

	public function __construct(IRequest $request)
	{
		$this->request = $request;
	}

	public function onRequest(Application $application, Request $request): void
	{
		if ($request->isMethod(Request::FORWARD)) {
			return;
		}

		$hub = SentrySdk::getCurrentHub();

		if ($hub->getTransaction() !== null) {
			throw new PerformanceException('Transaction already started');
		}

		$context = TransactionContext::fromSentryTrace($this->request->getHeader('sentry-trace') ?? '');
		$context->setOp('nette.request');
		$context->setName(sprintf(
			'%s %s %s',
			$request->getPresenterName(),
			$request->getParameter('action') ?? 'unknown', // @phpstan-ignore-line
			$request->getParameter('do') ?? '' // @phpstan-ignore-line
		));
		$context->setStartTimestamp(microtime(true));
		$context->setTags([
			'http.method' => $this->request->getMethod(),
			'http.url' => $this->request->getUrl()->getAbsoluteUrl(),
		]);

		$context->setData([
			'http.parameters' => $request->getParameters(),
		]);

		$hub->setSpan($hub->startTransaction($context));
	}

	/**
	 * End of main tracing transaction
	 */
	public function onShutdown(): void
	{
		$hub = SentrySdk::getCurrentHub();

		if ($hub->getTransaction() !== null) {
			$hub->getTransaction()->finish();
		}
	}

	public function hook(Application $application, string $hook): void
	{
		switch ($hook) {
			case 'onRequest':
				$application->onRequest[] = function (Application $application, Request $request): void {
					$this->onRequest($application, $request);
				};
				break;
			case 'onShutdown':
				$application->onShutdown[] = function (Application $application): void {
					$this->onShutdown();
				};
				break;
			default:
				throw new LogicalException('Unknown hook');
		}
	}

}
