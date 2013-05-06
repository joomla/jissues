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
use Joomla\Language\Language;
use Joomla\Profiler\Profiler;
use Joomla\Registry\Registry;

use Symfony\Component\HttpFoundation\Session\Session;

use Joomla\Tracker\Authentication\GitHub\GitHubUser;
use Joomla\Tracker\Authentication\User;
use Joomla\Tracker\Controller\AbstractTrackerController;
use Joomla\Tracker\Router\TrackerRouter;

/**
 * Joomla! Issue Tracker Application class
 *
 * @package  JTracker\Application
 * @since    1.0
 */
final class TrackerApplication extends AbstractWebApplication
{
	/**
	 * The scope of the application.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $scope = null;

	/**
	 * The client identifier.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $clientId = null;

	/**
	 * The application dispatcher object.
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
	 * @todo this has been created to avoid a conflict with the $session member var from the parent class.
	 *
	 * @var Session
	 */
	private $newSession = null;

	/**
	 * The user object.
	 *
	 * @var User
	 */
	private $user;

	/**
	 * The database driver object.
	 *
	 * @var    DatabaseDriver
	 */
	private $database;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var Profiler
	 */
	private $profiler;

	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Run the parent constructor
		parent::__construct();

		$this
			->loadConfiguration()
			->loadDispatcher();

		// Define the debug constant
		define('JDEBUG', $this->config->get('system.debug'));

		if (JDEBUG)
		{
			$this->profiler = new Profiler('Tracker');

			$this->mark('App started');
		}


		// Register the application to Factory
		// @todo remove factory usage
		Factory::$application = $this;
		Factory::$config = $this->config;

		// Load the library language file
		$this->getLanguage()->load('lib_joomla', JPATH_BASE);
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
			// Register the template to the config
			$template = $this->getTemplate(true);
			$this->set('theme', $template->template);
			$this->set('themeParams', $template->params);
			$this->set('themeFile', $this->input->get('tmpl', 'index') . '.php');

			// Set metadata
			// $document->setTitle('Joomla! CMS Issue Tracker');

			// Instantiate the router
			$router = new TrackerRouter($this->input, $this);
			$router->addMaps(json_decode(file_get_contents(JPATH_BASE . '/etc/routes.json'), true));
			$router->setControllerPrefix('Joomla\\Tracker\\Components');
			$router->setDefaultController('\\Tracker\\Controller\\DefaultController');

			// Fetch the controller
			/* @var AbstractTrackerController $controller */
			$controller = $router->getController($this->get('uri.route'));

			// Define the component path
			define('JPATH_COMPONENT', dirname(__DIR__) . '/Components/' . ucfirst($controller->getComponent()));

			// Execute the component
			$contents = $this->executeComponent($controller, strtolower($controller->getComponent()));

			$this->mark('App terminated');

			$contents .= $this->fetchDebugOutput();

			// Temporarily echo the $contents to prove it is working
			echo $contents;

			// $document->setBuffer($contents, 'component');
		}

		// Mop up any uncaught exceptions.
		catch (\Exception $e)
		{
			echo $e->getMessage();

			if (JDEBUG)
			{
				echo '<pre>'
					. str_replace(JPATH_BASE, 'JROOT', $e->getTraceAsString())
					. '</pre>';

				$this->mark('App terminated with an ERROR');

				echo $this->fetchDebugOutput();
			}

			$this->close($e->getCode());
		}
	}

	private function fetchDebugOutput()
	{
		if(!JDEBUG)
		{
			return '';
		}

		$debug = array();

		$debug[] = '<div class="debug">';
		$debug[] = '<p>Profile</p>';
		$debug[] =  $this->profiler->render();
		$debug[] = '</div>';

		return implode("\n", $debug);
	}

	public function mark($name)
	{
		if (!JDEBUG)
		{
			return;
		}

		$this->profiler->mark($name);
	}

	/**
	 * Initialize the configuration object.
	 *
	 * @throws \RuntimeException
	 *
	 * @return $this
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
		$template = $this->getTemplate(true)->template;
		$lang     = Factory::getLanguage();

		$lang->load('tpl_' . $template, JPATH_BASE, null, false, false)
			|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", null, false, false)
			|| $lang->load('tpl_' . $template, JPATH_BASE, $lang->getDefault(), false, false)
			|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", $lang->getDefault(), false, false);

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
		return md5(Factory::getConfig()->get('secret') . $seed);
	}

	/**
	 * Return the current state of the language filter.
	 *
	 * @since   1.0
	 *
	 * @return  boolean
	 */
	public function getLanguageFilter()
	{
		return false;
	}

	/**
	 * Get a session object.
	 *
	 * @return Session
	 */
	public function getSession()
	{
		if (is_null($this->newSession))
		{
			$this->newSession = new Session;
			$this->newSession->start();

			// @todo remove factory usage
			Factory::$session = $this->newSession;
		}

		return $this->newSession;
	}

	/**
	 * Get a user object.
	 *
	 * @param   integer  $id  The user id or the current user.
	 *
	 * @return User
	 */
	public function getUser($id = 0)
	{
		if ($id)
		{
			return new GitHubUser($id);
		}

		if (is_null($this->user))
		{
			$user = $this->getSession()->get('user');

			$this->user = ($user) ? : new GitHubUser;
		}

		return $this->user;
	}

	/**
	 * Get a database driver object.
	 *
	 * @return DatabaseDriver
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

			$this->database->setDebug($this->get('debug'));

			// @todo remove factory usage
			Factory::$database = $this->database;
		}

		return $this->database;
	}

	/**
	 * Get a language object.
	 *
	 * @return Language
	 */
	public function getLanguage()
	{
		if (is_null($this->language))
		{
			$this->language = Language::getInstance(
				$this->get('language'),
				$this->get('debug_lang')
			);

			// @todo remove factory usage
			Factory::$language = $this->language;
		}

		return $this->language;
	}

	/**
	 * Login or logout a user.
	 *
	 * @param   User  $user  The user object.
	 *
	 * @return $this
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

			$this->user  = $user;

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
	 * Method to get the component params
	 *
	 * @param   string  $component  The component to retrieve the params for
	 *
	 * @throws \RuntimeException
	 * @return  Registry  Component params
	 *
	 * @deprecated
	 * @since   1.0
	 */
	public function getParams($component = '')
	{
		throw new \RuntimeException('unsupported');

		// @return $component ? JComponentHelper::getParams($component) : new Registry;
	}

	/**
	 * Get the template information
	 *
	 * @param   boolean  $params  True to return the template params
	 *
	 * @return  mixed  String with the template name or an object containing the name and params
	 *
	 * @since   1.0
	 */
	public function getTemplate($params = false)
	{
		// Build the object
		$template = new \stdClass;
		$template->template = 'joomla';
		$template->params   = new Registry;

		if ($params)
		{
			return $template;
		}

		return $template->template;
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
		/* @var Registry $registry */
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
		/* @var Registry $registry */
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
}
