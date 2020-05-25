<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text;

use App\Text\Controller\CreateArticleController;
use App\Text\Controller\ListArticlesController;
use App\Text\Controller\ViewArticleController;
use App\Text\Model\ArticlesModel;
use Joomla\DI\Container;
use Joomla\Renderer\RendererInterface;
use Joomla\Router\Route;
use Joomla\Router\Router;
use Joomla\View\BaseHtmlView;
use JTracker\AppInterface;

/**
 * Text app
 *
 * @since  1.0
 */
class TextApp implements AppInterface
{
	/**
	 * Loads services for the component into the application's DI Container
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function loadServices(Container $container)
	{
		$this->registerRoutes($container->get('router'));
		$this->registerServices($container);
	}

	/**
	 * Registers the routes for the app
	 *
	 * @param   Router  $router  The application router
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerRoutes(Router $router)
	{
		// Register the component routes
		$maps = json_decode(file_get_contents(__DIR__ . '/routes.json'), true);

		if (!$maps)
		{
			throw new \RuntimeException('Invalid router file for the Text app: ' . __DIR__ . '/routes.json', 500);
		}

		foreach ($maps as $pattern => $route)
		{
			$methods    = $route['methods'] ?? [];
			$controller = $route['controller'];
			$rules      = $route['rules'] ?? [];
			$defaults   = $route['defaults'] ?? [];

			$router->addRoute(new Route($methods, $pattern, $controller, $rules, $defaults));
		}
	}

	/**
	 * Registers the services for the app
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerServices(Container $container)
	{
		$container->share(
			ListArticlesController::class,
			function (Container $container) {
				return new ListArticlesController(
					$container->get(ArticlesModel::class),
					$container->get('articles.list.view')
				);
			},
			true
		);

		$container->share(
			ViewArticleController::class,
			function (Container $container) {
				return new ViewArticleController(
					$container->get(ArticlesModel::class),
					$container->get('article.show.view')
				);
			},
			true
		);

		$container->share(
			CreateArticleController::class,
			function (Container $container) {
				return new CreateArticleController(
					$container->get('article.edit.view'),
					$container->get('db')
				);
			},
			true
		);

		$container->share(
			ArticlesModel::class,
			function (Container $container) {
				return new ArticlesModel($container->get('db'));
			},
			true
		);

		$container->share(
			'article.edit.view',
			function (Container $container) {
				$view = new BaseHtmlView(
					$container->get(ArticlesModel::class),
					$container->get(RendererInterface::class)
				);

				$view->setLayout('text/article.edit.twig');

				return $view;
			},
			true
		);

		$container->share(
			'articles.list.view',
			function (Container $container) {
				$view = new BaseHtmlView(
					$container->get(ArticlesModel::class),
					$container->get(RendererInterface::class)
				);

				$view->setLayout('text/articles.index.twig');

				return $view;
			},
			true
		);

		$container->share(
			'article.show.view',
			function (Container $container) {
				$view = new BaseHtmlView(
					$container->get(ArticlesModel::class),
					$container->get(RendererInterface::class)
				);

				$view->setLayout('text/article.show.twig');

				return $view;
			},
			true
		);
	}
}
