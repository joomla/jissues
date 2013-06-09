<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Application;

use Joomla\Application\AbstractWebApplication;
use Joomla\Controller\ControllerInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\Dispatcher;
use Joomla\Factory;
use Joomla\Github\Github;
use Joomla\Language\Language;
use Joomla\Registry\Registry;

use Joomla\Tracker\Authentication\Exception\AuthenticationException;
use Joomla\Tracker\Authentication\GitHub\GitHubUser;
use Joomla\Tracker\Authentication\User;
use Joomla\Tracker\Components\Debug\Logger\CallbackLogger;
use Joomla\Tracker\Components\Debug\TrackerDebugger;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\Components\Tracker\Table\ProjectsTable;
use Joomla\Tracker\Components\Tracker\TrackerProject;
use Joomla\Tracker\Controller\AbstractTrackerController;
use Joomla\Tracker\Router\Exception\RoutingException;
use Joomla\Tracker\Router\TrackerRouter;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Joomla! Tracker Application class.
 *
 * @package  JTracker\Application
 * @since    1.0
 */
final class TrackerApplication extends AbstractWebApplication
{
	/**
	 * The Dispatcher object.
	 *
	 * @var    Dispatcher
	 * @since  1.0
	 */
	protected $dispatcher;

	/**
	 * The application message queue.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $messageQueue = array();

	/**
	 * The name of the application.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $name = null;

	/**
	 * A session object.
	 *
	 * @var    Session
	 * @since  1.0
	 * @note   This has been created to avoid a conflict with the $session member var from the parent class.
	 */
	private $newSession = null;

	/**
	 * The User object.
	 *
	 * @var    User
	 * @since  1.0
	 */
	private $user;

	/**
	 * @var  TrackerProject
	 */
	private $project;

	/**
	 * The database driver object.
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $database;

	/**
	 * The Language object
	 *
	 * @var    Language
	 * @since  1.0
	 */
	private $language;

	/**
	 * The Debugger object
	 *
	 * @var  TrackerDebugger
	 * @since  1.0
	 */
	private $debugger;

	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Run the parent constructor
		parent::__construct();

		// Load the configuration object.
		$this->loadConfiguration();

		// Set the debugger.
		$this->debugger = new TrackerDebugger($this);

		// Register the event dispatcher
		$this->loadDispatcher();

		// Register the application to Factory
		// @todo Decouple from Factory
		Factory::$application = $this;
		Factory::$config = $this->config;

		// Load the library language file
		$this->getLanguage()->load('lib_joomla', JPATH_BASE);

		$this->mark('Application started');
	}

	/**
	 * Get a debugger object.
	 *
	 * @since   1.0
	 * @return TrackerDebugger
	 */
	public function getDebugger()
	{
		return $this->debugger;
	}

	/**
	 * Method to run the Web application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
		try
		{
			// Instantiate the router
			$router = new TrackerRouter($this->input, $this);
			$maps = json_decode(file_get_contents(JPATH_BASE . '/etc/routes.json'));

			if (!$maps)
			{
				throw new \RuntimeException('Invalid router file.', 500);
			}

			$router->addMaps($maps, true);
			$router->setControllerPrefix('Joomla\\Tracker\\Components');
			$router->setDefaultController('\\Tracker\\Controller\\DefaultController');

			// Fetch the controller
			/* @type AbstractTrackerController $controller */
			$controller = $router->getController($this->get('uri.route'));

			// Define the component path
			define('JPATH_COMPONENT', dirname(__DIR__) . '/Components/' . ucfirst($controller->getComponent()));

			// Execute the component
			$contents = $this->executeComponent($controller, strtolower($controller->getComponent()));

			$this->mark('Application terminated');

			if (JDEBUG)
			{
				$contents = str_replace('%%%DEBUG%%%', $this->debugger->getOutput(), $contents);
			}

			$this->setBody($contents);
		}
		catch (AuthenticationException $exception)
		{
			header('HTTP/1.1 403 Forbidden', true, 403);

			$this->mark('Application terminated with an AUTH EXCEPTION');

			$message = array();
			$message[] = 'Authentication failure';

			if (JDEBUG)
			{
				// The exceptions contains the User object and the action.
				if ($exception->getUser()->username)
				{
					$message[] = 'user: ' . $exception->getUser()->username;
					$message[] = 'id: ' . $exception->getUser()->id;
				}

				$message[] = 'action: ' . $exception->getAction();
			}

			$this->setBody($this->debugger->renderException($exception, implode("\n", $message)));
		}
		catch (RoutingException $exception)
		{
			header('HTTP/1.1 404 Not Found', true, 404);

			$this->mark('Application terminated with a ROUTING EXCEPTION');

			$message = JDEBUG ? $exception->getRawRoute() : '';

			$this->setBody($this->debugger->renderException($exception, $message));
		}
		catch (\Exception $exception)
		{
			header('HTTP/1.1 500 Internal Server Error', true, 500);

			$this->mark('Application terminated with an EXCEPTION');

			$this->setBody($this->debugger->renderException($exception));
		}
	}

	/**
	 * Add a profiler mark.
	 *
	 * @param   string  $text  The message for the mark.
	 *
	 * @return  TrackerApplication
	 *
	 * @since   1.0
	 */
	public function mark($text)
	{
		if (!JDEBUG)
		{
			return $this;
		}

		$this->debugger->mark($text);

		return $this;
	}

	/**
	 * Initialize the configuration object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function loadConfiguration()
	{
		// Set the configuration file path for the application.
		$file = JPATH_CONFIGURATION . '/config.json';

		// Verify the configuration exists and is readable.
		if (!is_readable($file))
		{
			throw new \RuntimeException('Configuration file does not exist or is unreadable.');
		}

		// Load the configuration file into an object.
		$config = json_decode(file_get_contents($file));

		if ($config === null)
		{
			throw new \RuntimeException(sprintf('Unable to parse the configuration file %s.', $file));
		}

		$this->config->loadObject($config);

		define('JDEBUG', $this->get('debug.system'));

		return $this;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  TrackerApplication
	 *
	 * @since   1.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		// For empty queue, if messages exists in the session, enqueue them first.
		if (!count($this->messageQueue))
		{
			$sessionQueue = $this->getSession()->get('application.queue');

			if (count($sessionQueue))
			{
				$this->messageQueue = $sessionQueue;
				$this->getSession()->set('application.queue', null);
			}
		}

		// Enqueue the message.
		$this->messageQueue[] = array('message' => $msg, 'type' => strtolower($type));

		return $this;
	}

	/**
	 * Execute the component.
	 *
	 * @param   ControllerInterface  $controller  The controller instance to execute
	 * @param   string               $component   The component being executed.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function executeComponent($controller, $component)
	{
		// Load template language files.
		$lang = $this->getLanguage();

		$lang->load('tpl_joomla', JPATH_BASE, null, false, false)
			|| $lang->load('tpl_joomla', JPATH_THEMES . '/joomla', null, false, false)
			|| $lang->load('tpl_joomla', JPATH_BASE, $lang->getDefault(), false, false)
			|| $lang->load('tpl_joomla', JPATH_THEMES . '/joomla', $lang->getDefault(), false, false);

		// Load common and local language files.
		$lang->load($component, JPATH_BASE, null, false, false)
			|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false);

		// Start an output buffer.
		ob_start();
		$controller->execute();

		return ob_get_clean();
	}

	/**
	 * Provides a secure hash based on a seed
	 *
	 * @param   string  $seed  Seed string.
	 *
	 * @return  string  A secure hash
	 *
	 * @since   1.0
	 */
	public static function getHash($seed)
	{
		return md5(Factory::getConfig()->get('acl.secret') . $seed);
	}

	/**
	 * Get a session object.
	 *
	 * @return  Session
	 *
	 * @since   1.0
	 */
	public function getSession()
	{
		if (is_null($this->newSession))
		{
			$this->newSession = new Session;
			$this->newSession->start();

			// @todo Decouple from Factory
			Factory::$session = $this->newSession;
		}

		return $this->newSession;
	}

	/**
	 * Get a user object.
	 *
	 * @param   integer  $id  The user id or the current user.
	 *
	 * @return  User
	 *
	 * @since   1.0
	 */
	public function getUser($id = 0)
	{
		if ($id)
		{
			return new GitHubUser($id);
		}

		if (is_null($this->user))
		{
			$this->user = ($this->getSession()->get('user'))
				? : new GitHubUser;
		}

		return $this->user;
	}

	/**
	 * Get a database driver object.
	 *
	 * @return  DatabaseDriver
	 *
	 * @since   1.0
	 */
	public function getDatabase()
	{
		if (is_null($this->database))
		{
			$this->database = DatabaseDriver::getInstance(
				array(
					'driver' => $this->get('database.driver'),
					'host' => $this->get('database.host'),
					'user' => $this->get('database.user'),
					'password' => $this->get('database.password'),
					'database' => $this->get('database.name'),
					'prefix' => $this->get('database.prefix')
				)
			);

			if ($this->get('debug.system'))
			{
				$this->database->setDebug(true);

				$this->database->setLogger(
					new CallbackLogger(
						array($this->debugger, 'addDatabaseEntry')
					)
				);
			}

			// @todo Decouple from Factory
			Factory::$database = $this->database;
		}

		return $this->database;
	}

	/**
	 * Get a language object.
	 *
	 * @return  Language
	 *
	 * @since   1.0
	 */
	public function getLanguage()
	{
		if (is_null($this->language))
		{
			$this->language = Language::getInstance(
				$this->get('language'),
				$this->get('debug_lang')
			);
		}

		return $this->language;
	}

	/**
	 * Login or logout a user.
	 *
	 * @param   User  $user  The user object.
	 *
	 * @return  TrackerApplication
	 *
	 * @since   1.0
	 */
	public function setUser(User $user = null)
	{
		if (is_null($user))
		{
			// Logout

			$this->user = new GitHubUser;

			$this->getSession()->set('user', $this->user);

			// @todo cleanup more ?
		}
		else
		{
			// Login

			$user->isAdmin = in_array($user->username, explode(',', $this->get('acl.admin_users')));

			$this->user = $user;

			$this->getSession()->set('user', $user);
		}

		return $this;
	}

	/**
	 * Get the system message queue.
	 *
	 * @return  array  The system message queue.
	 *
	 * @since   1.0
	 */
	public function getMessageQueue()
	{
		// For empty queue, if messages exists in the session, enqueue them.
		if (!count($this->messageQueue))
		{
			$sessionQueue = $this->getSession()->get('application.queue');

			if (count($sessionQueue))
			{
				$this->messageQueue = $sessionQueue;
				$this->getSession()->set('application.queue', null);
			}
		}

		return $this->messageQueue;
	}

	/**
	 * Set the system message queue.
	 *
	 * @param   array  $queue  The information to set in the message queue
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setMessageQueue(array $queue = array())
	{
		$this->messageQueue = $queue;
	}

	/**
	 * Method to get the application name.
	 *
	 * The dispatcher name is by default parsed using the class name, or it can be set
	 * by passing a $config['name'] in the class constructor.
	 *
	 * @return  string  The name of the dispatcher.
	 *
	 * @since   1.0
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets a user state.
	 *
	 * @param   string  $key      The path of the state.
	 * @param   mixed   $default  Optional default value, returned if the internal value is null.
	 *
	 * @return  mixed  The user state or null.
	 *
	 * @since   1.0
	 */
	public function getUserState($key, $default = null)
	{
		/* @type Registry $registry */
		$registry = $this->getSession()->get('registry');

		if (!is_null($registry))
		{
			return $registry->get($key, $default);
		}

		return $default;
	}

	/**
	 * Gets the value of a user state variable.
	 *
	 * @param   string  $key      The key of the user state variable.
	 * @param   string  $request  The name of the variable passed in a request.
	 * @param   string  $default  The default value for the variable if not found. Optional.
	 * @param   string  $type     Filter for the variable, for valid values see {@link JFilterInput::clean()}. Optional.
	 *
	 * @return  mixed The request user state.
	 *
	 * @since   1.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
	{
		$cur_state = $this->getUserState($key, $default);
		$new_state = $this->input->get($request, null, $type);

		// Save the new value only if it was set in this request.
		if ($new_state !== null)
		{
			$this->setUserState($key, $new_state);
		}
		else
		{
			$new_state = $cur_state;
		}

		return $new_state;
	}

	/**
	 * Sets the value of a user state variable.
	 *
	 * @param   string  $key    The path of the state.
	 * @param   string  $value  The value of the variable.
	 *
	 * @return  mixed  The previous state, if one existed.
	 *
	 * @since   1.0
	 */
	public function setUserState($key, $value)
	{
		/* @type Registry $registry */
		$registry = $this->getSession()->get('registry');

		if (!is_null($registry))
		{
			return $registry->set($key, $value);
		}

		return null;
	}

	/**
	 * Allows the application to load a custom or default dispatcher.
	 *
	 * The logic and options for creating this object are adequately generic for default cases
	 * but for many applications it will make sense to override this method and create event
	 * dispatchers, if required, based on more specific needs.
	 *
	 * @param   Dispatcher  $dispatcher  An optional dispatcher object. If omitted, the factory dispatcher is created.
	 *
	 * @return  TrackerApplication This method is chainable.
	 *
	 * @since   1.0
	 */
	public function loadDispatcher(Dispatcher $dispatcher = null)
	{
		$this->dispatcher = ($dispatcher === null) ? new Dispatcher : $dispatcher;

		return $this;
	}

	/**
	 * Redirect to another URL.
	 *
	 * If the headers have not been sent the redirect will be accomplished using a "301 Moved Permanently"
	 * or "303 See Other" code in the header pointing to the new location. If the headers have already been
	 * sent this will be accomplished using a JavaScript statement.
	 *
	 * @param   string   $url    The URL to redirect to. Can only be http/https URL
	 * @param   boolean  $moved  True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function redirect($url, $moved = false)
	{
		// Persist messages if they exist.
		if (count($this->messageQueue))
		{
			$this->getSession()->set('application.queue', $this->messageQueue);
		}

		parent::redirect($url, $moved);
	}

	/**
	 * Get the current project.
	 *
	 * @param   boolean  $reload  Reload the project.
	 *
	 * @since  1.0
	 * @return ProjectsTable
	 */
	public function getProject($reload = false)
	{
		if (is_null($this->project) || $reload)
		{
			$this->loadProject($reload);
		}

		return $this->project;
	}

	/**
	 * Get a GitHub object.
	 *
	 * @since  1.0
	 * @throws \RuntimeException
	 * @return Github
	 */
	public function getGitHub()
	{
		$options = new Registry;

		$token = $this->getSession()->get('gh_oauth_access_token');

		if ($token)
		{
			$options->set('gh.token', $token);
		}
		else
		{
			$options->set('api.username', $this->get('github.username'));
			$options->set('api.password', $this->get('github.password'));
		}

		// @todo temporary fix to avoid the "Socket" transport protocol - ADD: and the "stream"...
		$transport = \Joomla\Http\HttpFactory::getAvailableDriver($options, array('curl'));

		if (false == is_a($transport, 'Joomla\\Http\\Transport\\Curl'))
		{
			throw new \RuntimeException('Please enable cURL.');
		}

		$http = new \Joomla\Github\Http($options, $transport);

		// $app->debugOut(get_class($transport));

		// Instantiate J\Github
		$gitHub = new Github($options, $http);

		return $gitHub;
	}

	/**
	 * Load the current project.
	 *
	 * @param   boolean  $reload  Reload the project.
	 *
	 * @throws \InvalidArgumentException
	 * @since  1.0
	 * @return $this
	 */
	private function loadProject($reload = false)
	{
		$alias = $this->input->get('project_alias');

		$sessionProject = $this->getSession()->get('project');

		if ($alias)
		{
			// A Project is set
			if ($sessionProject
				&& $alias == $sessionProject->alias
				&& false == $reload)
			{
				// Use the Project stored in the session
				$this->project = $sessionProject;

				return $this;
			}

			// Change the project
			$projectModel = new ProjectModel;
			$project = $projectModel->getByAlias($alias);

			if (!$project)
			{
				// No project...
				throw new \InvalidArgumentException('Invalid project');
			}
		}
		else
		{
			// No Project set
			if ($sessionProject)
			{
				// No Project set - use the session Project.
				$project = $sessionProject;
			}
			else
			{
				// Nothing found - Set a default project !
				$projectModel = new ProjectModel;
				$project = $projectModel->getItem(1);
			}
		}

		$this->getSession()->set('project', $project);

		$this->project = $project;

		return $this;
	}
}
