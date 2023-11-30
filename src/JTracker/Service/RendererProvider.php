<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Renderer\RendererInterface;
use Joomla\Renderer\TwigRenderer;
use JTracker\Application\Application;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Twig\AssetsExtension;
use JTracker\Twig\CdnExtension;
use JTracker\Twig\FlashExtension;
use JTracker\Twig\PhpExtension;
use JTracker\Twig\Service\CdnRenderer;
use JTracker\Twig\Service\FlashMessageRetriever;
use JTracker\View\Renderer\ApplicationContext;
use JTracker\View\Renderer\DebugPathPackage;
use JTracker\View\Renderer\TrackerExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Twig\Cache\CacheInterface;
use Twig\Cache\FilesystemCache;
use Twig\Cache\NullCache;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

/**
 * Template renderer service provider
 *
 * @since  1.0
 */
class RendererProvider implements ServiceProviderInterface
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
		$container->alias('renderer.twig', RendererInterface::class)
			->alias('renderer', RendererInterface::class)
			->alias(TwigRenderer::class, RendererInterface::class)
			->share(
				RendererInterface::class,
				function (Container $container)
				{
					return new TwigRenderer($container->get('twig.environment'));
				},
				true
			);

		$container->alias(CacheInterface::class, 'twig.cache')
			->share(
				'twig.cache',
				function (Container $container)
				{
					/** @var \Joomla\Registry\Registry $config */
					$config = $container->get('config');

					// Pull down the renderer config
					$cacheConfig = $config->get('renderer.cache', false);

					if ($cacheConfig === false)
					{
						return new NullCache;
					}

					return new FilesystemCache(JPATH_ROOT . '/cache/' . $cacheConfig);
				},
				true
			);

		$container->alias(Environment::class, 'twig.environment')
			->share(
				'twig.environment',
				function (Container $container)
				{
					/** @var \Joomla\Registry\Registry $config */
					$config = $container->get('config');

					/** @var Application $app */
					$app = $container->get('app');

					$debug = $config->get('debug.template', false);

					$environment = new Environment(
						$container->get('twig.loader'),
						['debug' => $debug]
					);

					// Add the runtime loader
					$environment->addRuntimeLoader($container->get('twig.runtime.loader'));

					// Set up the environment's caching service
					$environment->setCache($container->get('twig.cache'));

					// Add the Twig extensions
					$environment->setExtensions($container->getTagged('twig.extension'));

					// Set the Twig environment globals
					$environment->addGlobal('user', $app->getUser());
					$environment->addGlobal('useCDN', $config->get('system.use_cdn', true));
					$environment->addGlobal('templateDebug', $debug);
					$environment->addGlobal('jdebug', JDEBUG);

					/** @var GitHubLoginHelper $loginHelper */
					$loginHelper = $container->get(GitHubLoginHelper::class);

					$environment->addGlobal('uri', $app->get('uri'));
					$environment->addGlobal('offset', $app->getUser()->params->get('timezone') ?: $config->get('system.offset', 'UTC'));
					$environment->addGlobal('loginUrl', $loginHelper->getLoginUri());

					// Add build commit if available
					if (file_exists(JPATH_ROOT . '/current_SHA'))
					{
						$data = trim(file_get_contents(JPATH_ROOT . '/current_SHA'));
						$environment->addGlobal('buildSHA', $data);
					}
					else
					{
						$environment->addGlobal('buildSHA', '');
					}

					return $environment;
				},
				true
			);

		$container->alias(AssetsExtension::class, 'twig.extension.assets')
			->share(
				'twig.extension.assets',
				function (Container $container)
				{
					return new AssetsExtension;
				},
				true
			);

		$container->alias(CdnExtension::class, 'twig.extension.cdn')
			->share(
				'twig.extension.cdn',
				function (Container $container)
				{
					return new CdnExtension;
				},
				true
			);

		$container->alias(DebugExtension::class, 'twig.extension.debug')
			->share(
				'twig.extension.debug',
				function (Container $container)
				{
					return new DebugExtension;
				},
				true
			);

		$container->alias(FlashExtension::class, 'twig.extension.flash')
			->share(
				'twig.extension.flash',
				function (Container $container)
				{
					return new FlashExtension;
				},
				true
			);

		$container->alias(PhpExtension::class, 'twig.extension.php')
			->share(
				'twig.extension.php',
				function (Container $container)
				{
					return new PhpExtension;
				},
				true
			);

		$container->alias(TrackerExtension::class, 'twig.extension.tracker')
			->share(
				'twig.extension.tracker',
				function (Container $container)
				{
					return new TrackerExtension;
				},
				true
			);

		$container->alias(LoaderInterface::class, 'twig.loader')
			->share(
				'twig.loader',
				function (Container $container)
				{
					return new FilesystemLoader([JPATH_TEMPLATES]);
				},
				true
			);

		$container->alias(ContainerRuntimeLoader::class, 'twig.runtime.loader')
			->share(
				'twig.runtime.loader',
				function (Container $container)
				{
					return new ContainerRuntimeLoader($container);
				},
				true
			);

		$container->alias(CdnRenderer::class, 'twig.service.cdn_renderer')
			->share(
				'twig.service.cdn_renderer',
				function (Container $container)
				{
					return new CdnRenderer(
						$container->get(Application::class),
						$container->get('cache'),
						$container->get('http'),
						$container->get(GitHubLoginHelper::class)
					);
				},
				true
			);

		$container->alias(FlashMessageRetriever::class, 'twig.service.flash_message_retriever')
			->share(
				'twig.service.flash_message_retriever',
				function (Container $container)
				{
					return new FlashMessageRetriever(
						$container->get(Application::class)
					);
				},
				true
			);

		$container->share(
			Packages::class,
			function (Container $container)
			{
				$version = file_exists(JPATH_ROOT . '/sha.txt') ? trim(file_get_contents(JPATH_ROOT . '/sha.txt')) : md5(\get_class($this));
				$context = new ApplicationContext($container->get('app'));

				return new Packages(
					new PathPackage('media', new StaticVersionStrategy($version), $context),
					[
						'debug'     => new DebugPathPackage(
							'media',
							new StaticVersionStrategy($version),
							$context,
							$container->get('app')->get('debug.template', false)
						),
						'noversion' => new PathPackage('media', new EmptyVersionStrategy, $context),
					]
				);
			},
			true
		);

		$this->tagTwigExtensions($container);
	}

	/**
	 * Tag services which are Twig extensions
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private function tagTwigExtensions(Container $container): void
	{
		/** @var \Joomla\Registry\Registry $config */
		$config = $container->get('config');

		$debug = $config->get('debug.template', false);

		$twigExtensions = [
			'twig.extension.assets',
			'twig.extension.cdn',
			'twig.extension.flash',
			'twig.extension.php',
			'twig.extension.tracker',
		];

		if ($debug)
		{
			$twigExtensions[] = 'twig.extension.debug';
		}

		$container->tag('twig.extension', $twigExtensions);
	}
}
