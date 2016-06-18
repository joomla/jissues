<?php
/**
 * Joomla! Framework Status Application
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\Status\Service;

use Joomla\Application\AbstractApplication;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Renderer\TwigRenderer;
use Joomla\Status\Renderer\TwigExtension;
use Joomla\Status\Renderer\TwigLoader;

/**
 * Twig renderer service provider
 *
 * @since  1.0
 */
class TwigRendererProvider implements ServiceProviderInterface
{
	/**
	 * Application object
	 *
	 * @var    AbstractApplication
	 * @since  1.0
	 */
	private $app;

	/**
	 * Constructor.
	 *
	 * @param   AbstractApplication  $app  Application object
	 *
	 * @since   1.0
	 */
	public function __construct(AbstractApplication $app)
	{
		$this->app = $app;
	}

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
		$container->share(
			'Joomla\\Renderer\\RendererInterface',
			function (Container $container) {
				/* @type  \Joomla\Registry\Registry  $config */
				$config = $container->get('config');

				// Instantiate the renderer object
				$rendererConfig = array_merge(
					(array) $config->get('template'),
					['path' => JPATH_TEMPLATES]
				);

				// If the cache isn't false, then it should be a file path relative to the app root
				$rendererConfig['cache'] = $rendererConfig['cache'] === false ? $rendererConfig['cache'] : JPATH_ROOT . '/' . $rendererConfig['cache'];

				// Instantiate the renderer object
				$renderer = new TwigRenderer($rendererConfig);

				// Add our Twig extension
				$renderer->getRenderer()->addExtension(new TwigExtension($this->app));

				// Add the debug extension if enabled
				if ($config->get('template.debug'))
				{
					$renderer->getRenderer()->addExtension(new \Twig_Extension_Debug);
				}

				// Set the Lexer object
				$renderer->getRenderer()->setLexer(
					new \Twig_Lexer($renderer->getRenderer(), ['delimiters' => [
						'tag_comment'  => ['{#', '#}'],
						'tag_block'    => ['{%', '%}'],
						'tag_variable' => ['{{', '}}']
					]])
				);

				return $renderer;
			},
			true
		);

		// Alias the renderer
		$container->alias('renderer', 'Joomla\\Renderer\\RendererInterface');
	}
}
