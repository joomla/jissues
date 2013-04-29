<?php
/**
 * @package     JTracker\Application
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Application;

use Joomla\Application\AbstractWebApplication;
use Joomla\Controller\ControllerInterface;
use Joomla\Crypt\Crypt;
use Joomla\Crypt\Key;
use Joomla\Crypt\Password\Simple;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\Dispatcher;
use Joomla\Factory;
use Joomla\Registry\Registry;
use Joomla\Session\Session;
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

		// Register the event dispatcher
		$this->loadDispatcher();

		/* Disable sessions for the moment
		// Enable sessions by default.
		if (is_null($this->get('session')))
		{
			$this->set('session', true);
		}

		// Set the session default name.
		if (is_null($this->get('session_name')))
		{
			$this->set('session_name', 'jissues');
		}

		// Create the session if a session name is passed.
		if ($this->get('session') !== false)
		{
			$this->loadSession();

			// Register the session with Factory
			Factory::$session = $this->getSession();
		}*/

		// Register the application to Factory
		Factory::$application = $this;

		// Load the database and register to Factory
		Factory::$database = $this->loadDatabase();

		// Load the library language file
		Factory::getLanguage()->load('lib_joomla', JPATH_BASE);
	}

	/**
	 * After the session has been started we need to populate it with some default values.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function afterSessionStart()
	{
		$session = Factory::getSession();

		if ($session->isNew())
		{
			$session->set('registry', new Registry('session'));
			$session->set('user', new JUser);
		}

		// TODO: At some point we need to get away from having session data always in the db.
		if ($this->get('sess_handler') == 'database')
		{
			$session = Factory::getSession();
			$db      = Factory::getDbo();

			// Remove expired sessions from the database.
			$time = time();
			if ($time % 2)
			{
				// The modulus introduces a little entropy, making the flushing less accurate
				// but fires the query less than half the time.
				$query = $db->getQuery(true);
				$query->delete($query->qn('#__session'));
				$query->where($query->qn('time') . ' < ' . $query->q((int) ($time - $session->getExpire())));

				$db->setQuery($query);
				$db->execute();
			}

			// Check to see the the session already exists.
			$handler = $this->get('sess_handler');
			if (($time % 2 || $session->isNew()) || ($session->isNew()))
			{
				$this->checkSession();
			}
		}
	}

	/**
	 * Checks the user session.
	 *
	 * If the session record doesn't exist, initialise it.
	 * If session is new, create session variables
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @todo    Figure out user portion of code
	 */
	public function checkSession()
	{
		$db      = Factory::getDbo();
		$session = Factory::getSession();
		//$user    = Factory::getUser();

		$query = $db->getQuery(true);
		$query->select($query->qn('session_id'));
		$query->from($query->qn('#__session'));
		$query->where($query->qn('session_id') . ' = ' . $query->q($session->getId()));

		$db->setQuery($query, 0, 1);
		$exists = $db->loadResult();

		// If the session record doesn't exist initialise it.
		if (!$exists)
		{
			$query->clear();

			$query->insert($query->qn('#__session'));
			if ($session->isNew())
			{
				$query->columns($query->qn('session_id') . ', ' . $query->qn('client_id') . ', ' . $query->qn('time'));
				$query->values($query->q($session->getId()) . ', ' . (int) $this->getClientId() . ', ' . $query->q((int) time()));
				$db->setQuery($query);
			}
			else
			{
				$query->columns(
					$query->qn('session_id') . ', ' . $query->qn('client_id') . ', ' . $query->qn('guest') . ', ' .
						$query->qn('time') . ', ' . $query->qn('userid') . ', ' . $query->qn('username')
				);
				$query->values(
					$query->q($session->getId()) . ', ' . (int) $this->getClientId() . ', ' . (int) $user->get('guest') . ', ' .
						$query->q((int) $session->get('session.timer.start')) . ', ' . (int) $user->get('id') . ', ' . $query->q($user->get('username'))
				);

				$db->setQuery($query);
			}

			// If the insert failed, exit the application.
			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				$this->close($e->getCode());
			}
		}
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
			//$document->setTitle('Joomla! CMS Issue Tracker');

			// Instantiate the router
			$router = new TrackerRouter($this->input, $this);
			$router->addMaps(json_decode(file_get_contents(JPATH_BASE . '/etc/routes.json'), true));
			$router->setControllerPrefix('Joomla\\Tracker\\Components');
			$router->setDefaultController('\\Tracker\\Controller\\DefaultController');

			// Fetch the controller
			$controller = $router->getController($this->get('uri.route'));

			// Define the component path
			define('JPATH_COMPONENT', dirname(__DIR__) . '/Components/' . ucfirst($controller->getComponent()));

			// Execute the component
			$contents = $this->executeComponent($controller, strtolower($controller->getComponent()));

			// Temporarily echo the $contents to prove it is working
			echo $contents;

			//$document->setBuffer($contents, 'component');
		}

			// Mop up any uncaught exceptions.
		catch (\Exception $e)
		{
			echo $e->getMessage();
			$this->close($e->getCode());
		}
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
			$session      = Factory::getSession();
			$sessionQueue = $session->get('application.queue');

			if (count($sessionQueue))
			{
				$this->messageQueue = $sessionQueue;
				$session->set('application.queue', null);
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
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function getLanguageFilter()
	{
		return false;
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
			$session      = Factory::getSession();
			$sessionQueue = $session->get('application.queue');

			if (count($sessionQueue))
			{
				$this->messageQueue = $sessionQueue;
				$session->set('application.queue', null);
			}
		}

		return $this->messageQueue;
	}

	/**
	 * Method to get the application name.
	 *
	 * The dispatcher name is by default parsed using the classname, or it can be set
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
	 * @return  Registry  Component params
	 *
	 * @since   1.0
	 */
	public function getParams($component = '')
	{
		return $component ? JComponentHelper::getParams($component) : new Registry;
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
		$registry = Factory::getSession()->get('registry');

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
	 * @return  The request user state.
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
	 * Load an object into the application configuration object.
	 *
	 * @return  TrackerApplication  Instance of $this to allow chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function loadConfiguration()
	{
		// Instantiate variables.
		$config = array();

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
	 * Method to create a database driver for the application.
	 *
	 * @return  DatabaseDriver  Database driver instance
	 *
	 * @see     DatabaseDriver::getInstance()
	 * @since   1.0
	 */
	public function loadDatabase()
	{
		$db = DatabaseDriver::getInstance(
			array(
				'driver' => $this->get('database.driver'),
				'host' => $this->get('database.host'),
				'user' => $this->get('database.user'),
				'password' => $this->get('database.password'),
				'database' => $this->get('database.name'),
				'prefix' => $this->get('database.prefix')
			)
		);

		// Select the database.
		$db->select($this->get('database.name'));

		// Set the debug flag.
		$db->setDebug($this->get('debug'));

		return $db;
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
	 * Allows the application to load a custom or default session.
	 *
	 * The logic and options for creating this object are adequately generic for default cases
	 * but for many applications it will make sense to override this method and create a session,
	 * if required, based on more specific needs.
	 *
	 * @param   Session  $session  An optional session object. If omitted, the session is created.
	 *
	 * @return  TrackerApplication  This method is chainable.
	 *
	 * @since   1.0
	 */
	public function loadSession(Session $session = null)
	{
		if ($session !== null)
		{
			$this->setSession($session);

			return $this;
		}

		// Generate a session name.
		$name = md5($this->get('secret') . $this->get('session_name', get_class($this)));

		// Calculate the session lifetime.
		$lifetime = (($this->get('sess_lifetime')) ? $this->get('sess_lifetime') * 60 : 900);

		// Get the session handler from the configuration.
		$handler = $this->get('sess_handler', 'none');

		// Initialize the options for Session.
		$options = array(
			'name' => $name,
			'expire' => $lifetime,
			'force_ssl' => $this->get('force_ssl')
		);

		$this->registerEvent('onAfterSessionStart', array($this, 'afterSessionStart'));

		// Instantiate the session object.
		$session = Session::getInstance($handler, $options);
		$session->initialise($this->input, $this->dispatcher);

		if ($session->getState() == 'expired')
		{
			$session->restart();
		}
		else
		{
			$session->start();
		}

		// Set the session object.
		$this->setSession($session);

		return $this;
	}

	/**
	 * Login authentication function.
	 *
	 * Username and encoded password are passed the onUserLogin event which
	 * is responsible for the user validation. A successful validation updates
	 * the current session record with the user's details.
	 *
	 * Username and encoded password are sent as credentials (along with other
	 * possibilities) to each observer (authentication plugin) for user
	 * validation.  Successful validation will update the current session with
	 * the user details.
	 *
	 * @param   array  $credentials  Array('username' => string, 'password' => string)
	 * @param   array  $options      Array('remember' => boolean)
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0
	 */
	public function login($credentials, $options = array())
	{
		// Get the global JAuthentication object.
		jimport('joomla.user.authentication');

		// Set the application login entry point
		if (!array_key_exists('entry_url', $options))
		{
			$options['entry_url'] = JUri::base() . 'index.php?option=com_users&task=login';
		}

		// Set the access control action to check.
		$options['action'] = 'core.login.site';

		$authenticate = JAuthentication::getInstance();
		$response     = $authenticate->authenticate($credentials, $options);

		if ($response->status === JAuthentication::STATUS_SUCCESS)
		{
			// Validate that the user should be able to login (different to being authenticated).
			// This permits authentication plugins blocking the user
			$authorisations = $authenticate->authorise($response, $options);
			foreach ($authorisations as $authorisation)
			{
				$denied_states = array(JAuthentication::STATUS_EXPIRED, JAuthentication::STATUS_DENIED);
				if (in_array($authorisation->status, $denied_states))
				{
					// Trigger onUserAuthorisationFailure Event.
					$this->triggerEvent('onUserAuthorisationFailure', array((array) $authorisation));

					// If silent is set, just return false.
					if (isset($options['silent']) && $options['silent'])
					{
						return false;
					}

					// Return the error.
					switch ($authorisation->status)
					{
						case JAuthentication::STATUS_EXPIRED:
							return JError::raiseWarning('102002', JText::_('JLIB_LOGIN_EXPIRED'));
							break;
						case JAuthentication::STATUS_DENIED:
							return JError::raiseWarning('102003', JText::_('JLIB_LOGIN_DENIED'));
							break;
						default:
							return JError::raiseWarning('102004', JText::_('JLIB_LOGIN_AUTHORISATION'));
							break;
					}
				}
			}

			// Import the user plugin group.
			JPluginHelper::importPlugin('user');

			// OK, the credentials are authenticated and user is authorised.  Lets fire the onLogin event.
			$results = $this->triggerEvent('onUserLogin', array((array) $response, $options));

			/*
			 * If any of the user plugins did not successfully complete the login routine
			 * then the whole method fails.
			 *
			 * Any errors raised should be done in the plugin as this provides the ability
			 * to provide much more information about why the routine may have failed.
			 */

			if (!in_array(false, $results, true))
			{
				// Set the remember me cookie if enabled.
				if (isset($options['remember']) && $options['remember'])
				{
					// Create the encryption key, apply extra hardening using the user agent string.
					$privateKey = static::getHash(@$_SERVER['HTTP_USER_AGENT']);

					$key      = new Key('simple', $privateKey, $privateKey);
					$crypt    = new Crypt(new Simple, $key);
					$rcookie  = $crypt->encrypt(serialize($credentials));
					$lifetime = time() + 365 * 24 * 60 * 60;

					// Use domain and path set in config for cookie if it exists.
					$cookie_domain = $this->get('cookie_domain', '');
					$cookie_path   = $this->get('cookie_path', '/');
					setcookie(static::getHash('JLOGIN_REMEMBER'), $rcookie, $lifetime, $cookie_path, $cookie_domain);
				}

				return true;
			}
		}

		// Trigger onUserLoginFailure Event.
		$this->triggerEvent('onUserLoginFailure', array((array) $response));

		// If silent is set, just return false.
		if (isset($options['silent']) && $options['silent'])
		{
			return false;
		}

		// If status is success, any error will have been raised by the user plugin
		if ($response->status !== JAuthentication::STATUS_SUCCESS)
		{
			JLog::add($response->error_message, JLog::WARNING, 'jerror');
		}

		return false;
	}

	/**
	 * Logout authentication function.
	 *
	 * Passed the current user information to the onUserLogout event and reverts the current
	 * session record back to 'anonymous' parameters.
	 * If any of the authentication plugins did not successfully complete
	 * the logout routine then the whole method fails. Any errors raised
	 * should be done in the plugin as this provides the ability to give
	 * much more information about why the routine may have failed.
	 *
	 * @param   integer  $userid   The user to load - Can be an integer or string - If string, it is converted to ID automatically
	 * @param   array    $options  Array('clientid' => array of client id's)
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	public function logout($userid = null, $options = array())
	{
		// Get a user object from the JApplication.
		$user = JFactory::getUser($userid);

		// Build the credentials array.
		$parameters['username'] = $user->get('username');
		$parameters['id']       = $user->get('id');

		// Set clientid in the options array if it hasn't been set already.
		if (!isset($options['clientid']))
		{
			$options['clientid'] = $this->getClientId();
		}

		// Import the user plugin group.
		JPluginHelper::importPlugin('user');

		// OK, the credentials are built. Lets fire the onLogout event.
		$results = $this->triggerEvent('onUserLogout', array($parameters, $options));

		// Check if any of the plugins failed. If none did, success.

		if (!in_array(false, $results, true))
		{
			// Use domain and path set in config for cookie if it exists.
			$cookie_domain = $this->get('cookie_domain', '');
			$cookie_path   = $this->get('cookie_path', '/');
			setcookie(static::getHash('JLOGIN_REMEMBER'), false, time() - 86400, $cookie_path, $cookie_domain);

			return true;
		}

		// Trigger onUserLoginFailure Event.
		$this->triggerEvent('onUserLogoutFailure', array($parameters));

		return false;
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
			Factory::getSession()->set('application.queue', $this->messageQueue);
		}

		parent::redirect($url, $moved);
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
		$registry = Factory::getSession()->get('registry');

		if (!is_null($registry))
		{
			return $registry->set($key, $value);
		}

		return null;
	}
}
