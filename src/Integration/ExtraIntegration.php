<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;

class ExtraIntegration extends BaseIntegration
{

	/** @var array<string, bool> */
	protected array $preset = [
		'memory' => true,
		'env' => true,
		'hostname' => true,
	];

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(protected array $data)
	{
	}

	public function setPreset(string $field, bool $enable = true): void
	{
		$this->preset[$field] = $enable;
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		$extra = array_merge($event->getExtra(), $this->data);

		if ($this->preset['memory']) {
			$extra['memory'] = (memory_get_peak_usage(true) / 1024 / 1024) . ' MB';
		}

		if ($this->preset['env']) {
			$extra['env'] = getenv();
		}

		if ($this->preset['env']) {
			$extra['hostname'] = gethostname();
		}

		$event->setExtra($extra);

		return $event;
	}

}
