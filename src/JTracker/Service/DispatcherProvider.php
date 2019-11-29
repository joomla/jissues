<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use App\Debug\TrackerDebugger;
use Joomla\Application\ApplicationEvents;
use Joomla\DI\Container;
use Joomla\DI\Exception\DependencyResolutionException;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\LazyServiceEventListener;
use Joomla\Registry\Registry;
use Joomla\Renderer\RendererInterface;
use Joomla\Renderer\TwigRenderer;
use JTracker\EventListener\AddDebugOutputToResponseListener;
use JTracker\EventListener\ErrorSubscriber;
use Psr\Log\LoggerInterface;

/**
 * Event dispatcher service provider
 *
 * @since  1.0
 */
class DispatcherProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->alias('dispatcher', DispatcherInterface::class)
			->alias(Dispatcher::class, DispatcherInterface::class)
			->share(
				DispatcherInterface::class,
				function (Container $container) {
					$dispatcher = new Dispatcher;

					foreach ($container->getTagged('event.subscriber') as $subscriber)
					{
						$dispatcher->addSubscriber($subscriber);
					}

					// Manually register the error subscriber as a lazy listener due to a circular dependency in constructing all the services
					$dispatcher->addListener(
						ApplicationEvents::ERROR,
						new LazyServiceEventListener($container, 'event.subscriber.error', 'handleWebError')
					);

					$dispatcher->addListener(
						ApplicationEvents::BEFORE_RESPOND,
						new LazyServiceEventListener($container, 'event.listener.add_debug_output_to_response')
					);

					return $dispatcher;
				},
				true
			);

		$container->alias(ErrorSubscriber::class, 'event.subscriber.error')
			->share(
				'event.subscriber.error',
				function (Container $container) {
					/** @var Registry $config */
					$config = $container->get('config');

					$rendererName = $config->get('renderer.type');

					// The renderer should exist in the container
					if (!$container->has("renderer.$rendererName"))
					{
						throw new DependencyResolutionException('Unsupported renderer: ' . $rendererName);
					}

					/** @var RendererInterface $renderer */
					$renderer = $container->get("renderer.$rendererName");

					if (!($renderer instanceof TwigRenderer))
					{
						throw new DependencyResolutionException('The error subscriber only supports the Twig renderer.');
					}

					$subscriber = new ErrorSubscriber($container->get(RendererInterface::class));
					$subscriber->setLogger($container->get(LoggerInterface::class));

					return $subscriber;
				},
				true
			);

		$container->alias(AddDebugOutputToResponseListener::class, 'event.listener.add_debug_output_to_response')
			->share(
				'event.listener.add_debug_output_to_response',
				function (Container $container) {
					return new AddDebugOutputToResponseListener($container->get(TrackerDebugger::class));
				},
				true
			);
	}
}
