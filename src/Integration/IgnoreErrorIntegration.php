<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use Contributte\Sentry\Utils\Regex;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IgnoreErrorIntegration extends BaseIntegration
{

	/** @var mixed[] */
	private array $options;

	/**
	 * @param mixed[] $options
	 */
	public function __construct(array $options = [])
	{
		$resolver = new OptionsResolver();
		$resolver->setDefaults([
			'ignore_exception_instance' => [],
			'ignore_exception_regex' => [],
			'ignore_message_regex' => [],
		]);

		$resolver->setAllowedTypes('ignore_exception_instance', ['array']);
		$resolver->setAllowedTypes('ignore_exception_regex', ['array']);
		$resolver->setAllowedTypes('ignore_message_regex', ['array']);

		$this->options = $resolver->resolve($options);
	}

	public function setup(HubInterface $hub, Event $event, EventHint $hint): ?Event
	{
		if ($this->isIgnoredByExceptionInstance($event)) {
			return null;
		}

		if ($this->isIgnoredByExceptionRegex($event)) {
			return null;
		}

		if ($this->isIgnoredByMessageRegex($event)) {
			return null;
		}

		return $event;
	}

	protected function isIgnoredByExceptionInstance(Event $event): bool
	{
		$exceptions = $event->getExceptions();

		if ($exceptions === []) {
			return false;
		}

		/** @var string[] $instances */
		$instances = $this->options['ignore_exception_instance'];
		foreach ($instances as $instance) {
			if ($exceptions[0]->getType() === $instance) {
				return true;
			}
		}

		return false;
	}

	protected function isIgnoredByExceptionRegex(Event $event): bool
	{
		$exceptions = $event->getExceptions();

		if ($exceptions === []) {
			return false;
		}

		/** @var string[] $regexes */
		$regexes = $this->options['ignore_exception_regex'];
		foreach ($regexes as $regex) {
			if (Regex::match($exceptions[0]->getValue(), $regex) !== null) {
				return true;
			}
		}

		return false;
	}

	protected function isIgnoredByMessageRegex(Event $event): bool
	{
		if ($event->getMessage() === null) {
			return false;
		}

		/** @var string[] $regexes */
		$regexes = $this->options['ignore_message_regex'];
		foreach ($regexes as $regex) {
			if (Regex::match($event->getMessage(), $regex) !== null) {
				return true;
			}
		}

		return false;
	}

}
