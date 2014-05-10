<?php
/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

use App\Debug\Database\SQLLogger;
use App\Debug\Database\SQLLoggerAwareInterface;

use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\View\Renderer\RendererInterface;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\View\AbstractTrackerHtmlView;
use JTracker\View\Renderer\TrackerExtension;

/**
 * Abstract Controller class for the Tracker Application
 *
 * @since  1.0
 */
abstract class AbstractTrackerController implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * The default view for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = '';

	/**
	 * The default layout for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'index';

	/**
	 * The app being executed.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $app;

	/**
	 * View object
	 *
	 * @var    \Joomla\View\AbstractHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Model object
	 *
	 * @var    \Joomla\Model\AbstractModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Detect the App name
		if (empty($this->app))
		{
			// Get the fully qualified class name for the current object
			$fqcn = (get_class($this));

			// Strip the base app namespace off
			$className = str_replace('App\\', '', $fqcn);

			// Explode the remaining name into an array
			$classArray = explode('\\', $className);

			// Set the app as the first object in this array
			$this->app = $classArray[0];
		}
	}

	/**
	 * Method to check whether an ID is in the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  boolean  True if the ID is in the edit list.
	 *
	 * @since   1.0
	 */
	protected function checkEditId($context, $id)
	{
		if ($id)
		{
			$app    = $this->getContainer()->get('app');
			$values = (array) $app->getUserState($context . '.id');

			$result = in_array((int) $id, $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				$this->getApplication()->getLogger()->info(
					sprintf(
						'Checking edit ID %s.%s: %d %s',
						$context,
						$id,
						(int) $result,
						str_replace("\n", ' ', print_r($values, 1))
					)
				);
			}

			return $result;
		}
		else
		{
			// No id for a new item.
			return true;
		}
	}

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method allows chiaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		// Get the input
		/* @type Input $input */
		$input = $this->getContainer()->get('app')->input;

		// Get some data from the request
		$viewName   = $input->getWord('view', $this->defaultView);
		$viewFormat = $input->getWord('format', 'html');
		$layoutName = $input->getCmd('layout', $this->defaultLayout);

		if (!$viewName)
		{
			$parts = explode('\\', get_class($this));
			$viewName = strtolower($parts[count($parts) - 1]);
		}

		$base = '\\App\\' . $this->app;

		$viewClass  = $base . '\\View\\' . ucfirst($viewName) . '\\' . ucfirst($viewName) . ucfirst($viewFormat) . 'View';
		$modelClass = $base . '\\Model\\' . ucfirst($viewName) . 'Model';

		// If a model doesn't exist for our view, revert to the default model
		if (!class_exists($modelClass))
		{
			$modelClass = $base . '\\Model\\DefaultModel';

			// If there still isn't a class, panic.
			if (!class_exists($modelClass))
			{
				throw new \RuntimeException(
					sprintf(
						'No model found for view %s or a default model for %s', $viewName, $this->app
					)
				);
			}
		}

		// Make sure the view class exists, otherwise revert to the default
		if (!class_exists($viewClass))
		{
			$viewClass = '\\JTracker\\View\\TrackerDefaultView';
		}

		// Register the templates paths for the view
		$paths = array();

		$sub = ('php' == $this->getContainer()->get('app')->get('renderer.type')) ? '/php' : '';

		$path = JPATH_TEMPLATES . $sub . '/' . strtolower($this->app);

		if (is_dir($path))
		{
			$paths[] = $path;
		}

		// @$this->model = $this->getContainer()->buildObject($modelClass);

		if (in_array($this->app, ['Documentor', 'Text']))
		{
			// These Apps are handled with a Doctrine model
			$this->model = new $modelClass(
				new Registry($this->getContainer()->get('app')->get('database')),
				[JPATH_ROOT . '/src/App'],
				$this->getContainer()->get('app')->get('debug.database')
			);

			if ($this->model instanceof SQLLoggerAwareInterface)
			{
				$this->model->setSQLLogger(new SQLLogger($this->getContainer()->get('debugger')));
			}
		}
		else
		{
			$this->model = new $modelClass($this->getContainer()->get('db'), $this->getContainer()->get('app')->input);
		}

		// Create the view
		/* @type AbstractTrackerHtmlView $view */
		$this->view = new $viewClass(
			$this->model,
			$this->fetchRenderer($paths)
		);

		$this->view->setLayout($viewName . '.' . $layoutName);

		return $this;
	}

	/**
	 * Execute the controller.
	 *
	 * This is a generic method to execute and render a view and is not suitable for tasks.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		try
		{
			// Render our view.
			$contents = $this->view->render();
		}
		catch (\Exception $e)
		{
			$contents = $this->getContainer()->get('app')->getDebugger()->renderException($e);
		}

		return $contents;
	}

	/**
	 * Returns the current app
	 *
	 * @return  string  The app being executed.
	 *
	 * @since   1.0
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * Method to add a record ID to the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function holdEditId($context, $id)
	{
		$app = $this->getContainer()->get('app');
		$values = (array) $app->getUserState($context . '.id');

		// Add the id to the list if non-zero.
		if (!empty($id))
		{
			array_push($values, (int) $id);
			$values = array_unique($values);
			$app->setUserState($context . '.id', $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				$this->getApplication()->getLogger()->info(
					sprintf(
						'Holding edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					)
				);
			}
		}
	}

	/**
	 * Method to check whether an ID is in the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function releaseEditId($context, $id)
	{
		$app    = $this->getContainer()->get('app');
		$values = (array) $app->getUserState($context . '.id');

		// Do a strict search of the edit list values.
		$index = array_search((int) $id, $values, true);

		if (is_int($index))
		{
			unset($values[$index]);
			$app->setUserState($context . '.id', $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				$this->getApplication()->getLogger()->info(
					sprintf(
						'Releasing edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					)
				);
			}
		}
	}

	/**
	 * Get a renderer object.
	 *
	 * @param   string|array  $templatesPaths  A path or an array of paths where to look for templates.
	 *
	 * @return  RendererInterface
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function fetchRenderer($templatesPaths)
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$rendererName = $application->get('renderer.type');

		$className = 'JTracker\\View\\Renderer\\' . ucfirst($rendererName);

		// Check if the specified renderer exists in the application
		if (false == class_exists($className))
		{
			$className = 'Joomla\\View\\Renderer\\' . ucfirst($rendererName);

			// Check if the specified renderer exists in the Framework
			if (false == class_exists($className))
			{
				throw new \RuntimeException(sprintf('Invalid renderer: %s', $rendererName));
			}
		}

		$config = array();

		switch ($rendererName)
		{
			case 'twig':
				$config['templates_base_dir'] = JPATH_TEMPLATES;
				$config['environment']['debug'] = JDEBUG ? true : false;

				break;

			case 'mustache':
				$config['templates_base_dir'] = JPATH_TEMPLATES;

				// . '/partials';
				$config['partials_base_dir'] = JPATH_TEMPLATES;

				$config['environment']['debug'] = JDEBUG ? true : false;

				break;

			case 'php':
				$config['templates_base_dir'] = JPATH_TEMPLATES . '/php';
				$config['debug'] = JDEBUG ? true : false;

				break;

			default:
				throw new \RuntimeException('Unsupported renderer: ' . $rendererName);
				break;
		}

		// Load the renderer.
		/* @type RendererInterface $renderer */
		$renderer = new $className($config);

		// Register tracker's extension.
		$renderer->addExtension(new TrackerExtension($this->getContainer()));

		// Register additional paths.
		if (!empty($templatesPaths))
		{
			$renderer->setTemplatesPaths($templatesPaths, true);
		}

		$gitHubHelper = new GitHubLoginHelper($this->getContainer());

		$renderer
			->set('loginUrl', $gitHubHelper->getLoginUri())
			->set('user', $application->getUser());

		// Retrieve and clear the message queue
		$renderer->set('flashBag', $application->getMessageQueue());
		$application->clearMessageQueue();

		// Add build commit if available
		if (file_exists(JPATH_ROOT . '/current_SHA'))
		{
			$data = trim(file_get_contents(JPATH_ROOT . '/current_SHA'));
			$renderer->set('buildSHA', $data);
		}
		else
		{
			$renderer->set('buildSHA', '');
		}

		return $renderer;
	}
}
