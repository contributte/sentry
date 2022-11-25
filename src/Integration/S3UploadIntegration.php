<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Contributte\Sentry\Exception\Runtime\UploadException;
use Contributte\Sentry\Upload\S3Uploader;
use Nette\DI\Container;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;
use Tracy\Debugger;
use Tracy\Logger;

class S3UploadIntegration extends BaseIntegration
{

	/** @var Container */
	protected $context;

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		/** @var S3Uploader|null $uploader */
		$uploader = $this->context->getByType(S3Uploader::class, false);

		// Required services are missing
		if ($uploader === null) {
			return $event;
		}

		$exception = $hint->exception;

		// No exception
		if ($exception === null) {
			return $event;
		}

		// Use logger from Tracy to calculate filename
		$logger = new Logger(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
		$file = $logger->getExceptionFile($exception);

		// Render bluescreen to file
		$bs = Debugger::getBlueScreen();
		$bs->renderToFile($exception, $file);

		// Upload file
		try {
			$uploaded = $uploader->upload($file);
			$event->setTags([
				'tracy_file' => $uploaded['url'],
			]);
		} catch (UploadException $e) {
			// Do nothing
		}

		return $event;
	}

}
