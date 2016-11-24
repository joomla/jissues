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
use Joomla\Renderer\MustacheRenderer;
use Joomla\Renderer\TwigRenderer;
use JTracker\View\Renderer\TrackerExtension;

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

		$container->alias('renderer.mustache', MustacheRenderer::class)
			->share(
				MustacheRenderer::class,
				function (Container $container) {
					$engine = new \Mustache_Engine(
						[
							'loader'          => new \Mustache_Loader_FilesystemLoader(JPATH_TEMPLATES),
							'partials_loader' => new \Mustache_Loader_FilesystemLoader(JPATH_TEMPLATES),
						]
					);

					return new MustacheRenderer($engine);
				},
				true
			);
	}
}
