<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker;

use App\Debug\TrackerDebugger;
use App\Projects\Model\ProjectModel;
use App\Projects\TrackerProject;

use g11n\g11n;

use Joomla\Application\AbstractWebApplication;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Registry\Registry;

use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Authentication\User;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Router\Exception\RoutingException;
use JTracker\Router\TrackerRouter;
use JTracker\Service\ApplicationProvider;
use JTracker\Service\ConfigurationProvider;
use JTracker\Service\DatabaseProvider;
use JTracker\Service\DebuggerProvider;
use JTracker\Service\GitHubProvider;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Joomla Tracker web application class
 *
 * @since  1.0
 */
final class Application extends AbstractWebApplication implements DispatcherAwareInterface
{
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
	 * The Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	private $project;

	/**
	 * DI Container
	 *
	 * @var    Container
	 * @since  1.0
	 */
	private $container = null;

	/**
	 * Event Dispatcher
	 *
	 * @var    Dispatcher
	 * @since  1.0
	 */
	private $dispatcher;

	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Run the parent constructor
		parent::__construct();

		// Build the DI Container
		$this->container = (new Container)
			->registerServiceProvider(new ApplicationProvider($this))
			->registerServiceProvider(new ConfigurationProvider($this->config))
			->registerServiceProvider(new DatabaseProvider)
			->registerServiceProvider(new DebuggerProvider)
			->registerServiceProvider(new GitHubProvider);

		$this->loadLanguage()
			->mark('Application started');

		// Register the global dispatcher
		$this->setDispatcher(new Dispatcher);
	}

	/**
	 * Get a debugger object.
	 *
	 * @return  TrackerDebugger
	 *
	 * @since   1.0
	 */
	public function getDebugger()
	{
		return $this->container->get('debugger');
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
			$router = new TrackerRouter($this->container, $this->input);
			$maps = json_decode(file_get_contents(JPATH_ROOT . '/etc/routes.json'));

			if (!$maps)
			{
				throw new \RuntimeException('Invalid router file.', 500);
			}

			$router->addMaps($maps, true);
			$router->setControllerPrefix('\\App');
			$router->setDefaultController('\\Tracker\\Controller\\DefaultController');

			// Fetch the controller
			/* @type AbstractTrackerController $controller */
			$controller = $router->getController($this->get('uri.route'));

			$this->mark('Controller->initialize()');

			$controller->initialize();

			// Execute the App

			// Define the app path
			define('JPATH_APP', JPATH_ROOT . '/src/App/' . ucfirst($controller->getApp()));

			// Load the App language file
			g11n::loadLanguage($controller->getApp(), 'App');

			$this->mark('Controller->execute()');

			$contents = $controller->execute();

			if (!$contents)
			{
				throw new \UnexpectedValueException(sprintf('The %s controllers execute() method did not return anything!', get_class($controller)));
			}

			$this->mark('Application terminated OK');

			$contents = str_replace('%%%DEBUG%%%', $this->getDebugger()->getOutput(), $contents);

			$this->setBody($contents);
		}
		catch (AuthenticationException $exception)
		{
			header('HTTP/1.1 403 Forbidden', true, 403);

			$this->mark('Application terminated with an AUTH EXCEPTION');

			$context = array();
			$context['message'] = 'Authentication failure';

			if (JDEBUG)
			{
				// The exceptions contains the User object and the action.
				if ($exception->getUser()->username)
				{
					$context['user'] = $exception->getUser()->username;
					$context['id'] = $exception->getUser()->id;
				}

				$context['action'] = $exception->getAction();
			}

			$this->setBody($this->getDebugger()->renderException($exception, $context));
		}
		catch (RoutingException $exception)
		{
			header('HTTP/1.1 404 Not Found', true, 404);

			$this->mark('Application terminated with a ROUTING EXCEPTION');

			$context = JDEBUG ? array('message' => $exception->getRawRoute()) : array();

			$this->setBody($this->getDebugger()->renderException($exception, $context));
		}
		catch (\Exception $exception)
		{
			header('HTTP/1.1 500 Internal Server Error', true, 500);

			$this->mark('Application terminated with an EXCEPTION');

			$this->setBody($this->getDebugger()->renderException($exception));
		}
	}

	/**
	 * Add a profiler mark.
	 *
	 * @param   string  $text  The message for the mark.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function mark($text)
	{
		if ($this->get('debug.system'))
		{
			$this->getDebugger()->mark($text);
		}

		return $this;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		$this->getSession()->getFlashBag()->add($type, $msg);

		return $this;
	}

	/**
	 * Get the dispatcher object.
	 *
	 * @return  Dispatcher
	 *
	 * @since   1.0
	 */
	public function getDispatcher()
	{
		return $this->dispatcher;
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

			$registry = $this->newSession->get('registry');

			if (is_null($registry))
			{
				$this->newSession->set('registry', new Registry('session'));
			}
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
			return new GitHubUser($this->getProject(), $this->container->get('db'), $id);
		}

		if (is_null($this->user))
		{
			if ($this->user = $this->getSession()->get('jissues_user'))
			{
				// @todo Ref #275
				$this->user->setDatabase($this->container->get('db'));
				$this->user->getProject()->setDatabase($this->container->get('db'));
			}
			else
			{
				$this->user = new GitHubUser($this->getProject(), $this->container->get('db'));
			}
		}

		return $this->user;
	}

	/**
	 * Get a language object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	protected function loadLanguage()
	{
		// Get the language tag from user input.
		$lang = $this->input->get('lang');

		if ($lang)
		{
			if (false == in_array($lang, $this->get('languages')))
			{
				// Unknown language from user input - fall back to default
				$lang = g11n::getDefault();
			}

			if (false == in_array($lang, $this->get('languages')))
			{
				// Unknown default language - Fall back to British.
				$lang = 'en-GB';
			}

			// Store the language tag to the session.
			$this->getSession()->set('lang', $lang);
		}
		else
		{
			// Get the language tag from the session - Default to British.
			$lang = $this->getSession()->get('lang', 'en-GB');
		}

		if ($lang)
		{
			// Set the current language if anything has been found.
			g11n::setCurrent($lang);
		}

		// Set language debugging.
		g11n::setDebug($this->get('debug.language'));

		// Set the language cache directory.
		if ('vagrant' == getenv('JTRACKER_ENVIRONMENT'))
		{
			g11n::setCacheDir('/tmp');
		}
		else
		{
			g11n::setCacheDir(JPATH_ROOT . '/cache');
		}

		// Load the core language file.
		g11n::addDomainPath('Core', JPATH_ROOT . '/src');
		g11n::loadLanguage('JTracker', 'Core');

		// Load template language files.
		g11n::addDomainPath('Template', JPATH_ROOT . '/templates');
		g11n::loadLanguage('JTracker', 'Template');

		// Add the App domain path.
		g11n::addDomainPath('App', JPATH_ROOT . '/src/App');

		if ($this->get('debug.system')
			|| $this->get('debug.database')
			|| $this->get('debug.language'))
		{
			// Load the Debug App language file.
			g11n::loadLanguage('Debug', 'App');
		}

		return $this;
	}

	/**
	 * Set the dispatcher to use.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher to use.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;

		return $this;
	}

	/**
	 * Login or logout a user.
	 *
	 * @param   User  $user  The user object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function setUser(User $user = null)
	{
		if (is_null($user))
		{
			// Logout
			$this->user = new GitHubUser($this->getProject(), $this->container->get('db'));

			$this->getSession()->set('jissues_user', $this->user);

			// @todo cleanup more ?
		}
		elseif($user instanceof User)
		{
			// Login
			$user->isAdmin = in_array($user->username, $this->get('acl.admin_users'));

			$this->user = $user;

			$this->getSession()->set('jissues_user', $user);
		}
		else
		{
			throw new \UnexpectedValueException('Wrong parameter when instantiating a new user object.');
		}

		return $this;
	}

	/**
	 * Clear the system message queue.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function clearMessageQueue()
	{
		$this->getSession()->getFlashBag()->clear();
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
		return $this->getSession()->getFlashBag()->peekAll();
	}

	/**
	 * Set the system message queue for a given type.
	 *
	 * @param   string  $type     The type of message to set
	 * @param   mixed   $message  Either a single message or an array of messages
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setMessageQueue($type, $message = '')
	{
		$this->getSession()->getFlashBag()->set($type, $message);
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
	 * @return  mixed  The request user state.
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
	 * Get the current project.
	 *
	 * @param   boolean  $reload  Reload the project.
	 *
	 * @return  TrackerProject
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function getProject($reload = false)
	{
		if (is_null($this->project) || $reload)
		{
			$alias = $this->input->get('project_alias');

			if ($alias)
			{
				// Change the project
				$project = (new ProjectModel($this->container->get('db')))
					->getByAlias($alias);

				if (!$project)
				{
					// No project...
					throw new \InvalidArgumentException('Invalid project');
				}
			}
			else
			{
				$sessionAlias = $this->getSession()->get('project_alias');

				// No Project set
				if ($sessionAlias)
				{
					// Found a session Project.
					$project = (new ProjectModel($this->container->get('db')))
						->getByAlias($sessionAlias);
				}
				else
				{
					// Nothing found - Get a default project !
					$project = (new ProjectModel($this->container->get('db')))
						->getItem(1);
				}
			}

			$this->getSession()->set('project_alias', $project->alias);
			$this->input->set('project_id', $project->project_id);

			$this->project = $project;
		}

		return $this->project;
	}
}
