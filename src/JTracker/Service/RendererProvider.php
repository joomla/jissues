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
use Joomla\Renderer\TwigRenderer;
use JTracker\View\Renderer\ApplicationContext;
use JTracker\View\Renderer\DebugPathPackage;
use JTracker\View\Renderer\TrackerExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

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
		$container->alias('renderer.twig', TwigRenderer::class)
			->share(
				TwigRenderer::class,
				function (Container $container) {
					/** @var \Joomla\Registry\Registry $config */
					$config = $container->get('config');

					$rendererConfig = [
						'debug' => (bool) $config->get('debug.template', false),
						'cache' => $config->get('renderer.cache', false) ? JPATH_ROOT . '/cache/' . $config->get('renderer.cache') : false,
					];

					// Instantiate the Twig environment
					$environment = new \Twig_Environment(new \Twig_Loader_Filesystem([JPATH_TEMPLATES]), $rendererConfig);

					// Add our Twig extension
					$environment->addExtension(new TrackerExtension($container));

					// Add the debug extension if enabled
					if ($rendererConfig['debug'])
					{
						$environment->addExtension(new \Twig_Extension_Debug);
					}

					// Set the Lexer object
					$environment->setLexer(
						new \Twig_Lexer(
							$environment, [
								'delimiters' => [
									'tag_comment'  => ['{#', '#}'],
									'tag_block'    => ['{%', '%}'],
									'tag_variable' => ['{{', '}}'],
								],
							]
						)
					);

					return new TwigRenderer($environment);
				},
				true
			);

		$container->share(
			Packages::class,
			function (Container $container) {
				$version = file_exists(JPATH_ROOT . '/sha.txt') ? trim(file_get_contents(JPATH_ROOT . '/sha.txt')) : md5(get_class($this));
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
			}
		);
	}
}
