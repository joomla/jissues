<?php
/**
 * @package     JTracker
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Joomla! Issue Tracker Application class
 *
 * @package     JTracker
 * @subpackage  Application
 * @since       1.0
 */
abstract class JApplicationTracker extends JApplicationWeb
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
	 * @param   mixed  $input   An optional argument to provide dependency injection for the application's
	 *                          input object.  If the argument is a JInput object that object will become
	 *                          the application's input object, otherwise a default input object is created.
	 * @param   mixed  $config  An optional argument to provide dependency injection for the application's
	 *                          config object.  If the argument is a JRegistry object that object will become
	 *                          the application's config object, otherwise a default config object is created.
	 * @param   mixed  $client  An optional argument to provide dependency injection for the application's
	 *                          client object.  If the argument is a JApplicationWebClient object that object will become
	 *                          the application's client object, otherwise a default client object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(JInput $input = null, JRegistry $config = null, JApplicationWebClient $client = null)
	{
		// Run the parent constructor
		parent::__construct();

		// Register the event dispatcher
		$this->loadDispatcher();

		// Enable sessions by default.
		if (is_null($this->config->get('session')))
		{
			$this->config->set('session', true);
		}

		// Set the session default name.
		if (is_null($this->config->get('session_name')))
		{
			$this->config->set('session_name', 'jissues');
		}

		// Create the session if a session name is passed.
		if ($this->config->get('session') !== false)
		{
			$this->loadSession();

			// Register the session with JFactory
			JFactory::$session = $this->getSession();
		}

		// Register the application to JFactory
		JFactory::$application = $this;
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
		parent::afterSessionStart();

		// TODO: At some point we need to get away from having session data always in the db.
		if ($this->getCfg('sess_handler') == 'database')
		{
			$session = JFactory::getSession();
			$db      = JFactory::getDBO();

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
			$handler = $this->getCfg('sess_handler');
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
	 */
	public function checkSession()
	{
		$db      = JFactory::getDbo();
		$session = JFactory::getSession();
		$user    = JFactory::getUser();

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
				jexit($e->getMessage());
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
			// Load the document to the API
			$this->loadDocument();

			// Set up the params
			$document = $this->getDocument();

			// Register the document object with JFactory
			JFactory::$document = $document;

			// Register the template to the config
			$template = $this->getTemplate(true);
			$this->set('theme', $template->template);
			$this->set('themeParams', $template->params);
			$this->set('themeFile', $this->input->get('tmpl', 'index') . '.php');

			// Set metadata
			$document->setTitle('Joomla! CMS Issue Tracker');

			// Load the component
			$component = $this->input->get('option', 'com_tracker');

			$legacyComponents = array();

			if (in_array($component, $legacyComponents))
			{
				// Legacy component rendering
				$contents = JComponentHelper::renderComponent($component);
			}
			else
			{
				// Fetch the controller
				$controller = $this->fetchController($component, $this->input->getCmd('task'));

				// Execute the component
				$contents = $this->executeComponent($controller, $component);
			}

			$document->setBuffer($contents, 'component');
		}

			// Mop up any uncaught exceptions.
		catch (Exception $e)
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
	 * @return  JApplicationTracker
	 *
	 * @since   1.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		// For empty queue, if messages exists in the session, enqueue them first.
		if (!count($this->messageQueue))
		{
			$session      = JFactory::getSession();
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
	 * @param   JController  $controller  The controller instance to execute
	 * @param   string       $component   The component being executed.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	public function executeComponent($controller, $component)
	{
		// Load template language files.
		$template = $this->getTemplate(true)->template;
		$lang     = JFactory::getLanguage();

		$lang->load('tpl_' . $template, JPATH_BASE, null, false, false)
			|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", null, false, false)
			|| $lang->load('tpl_' . $template, JPATH_BASE, $lang->getDefault(), false, false)
			|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", $lang->getDefault(), false, false);

		// Load common and local language files.
		$lang->load($component, JPATH_BASE, null, false, false)
			|| $lang->load($component, JPATH_COMPONENT, null, false, false)
			|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
			|| $lang->load($component, JPATH_COMPONENT, $lang->getDefault(), false, false);

		// Start an output buffer.
		ob_start();
		$controller->execute();

		return ob_get_clean();
	}

	/**
	 * Method to get a controller object.
	 *
	 * @param   string  $component  The component being called
	 * @param   string  $task       The task being executed in the component
	 *
	 * @return  JController
	 *
	 * @since   1.0
	 * @throws  RuntimeException
	 */
	protected function fetchController($component, $task)
	{
		if (is_null($task))
		{
			$task = 'default';
		}

		// Strip com_ off the component
		$base = substr($component, 4);

		// Set the controller class name based on the task
		$class = ucfirst($base) . 'Controller' . ucfirst($task);

		// Define component path.
		define('JPATH_COMPONENT', JPATH_BASE . '/components/' . $component);

		// Register the component with the autoloader
		JLoader::registerPrefix(ucfirst($base), JPATH_COMPONENT);

		// If the requested controller exists let's use it.
		if (class_exists($class))
		{
			return new $class;
		}
		// See if there's an action class in the libraries if we aren't calling the default task
		elseif ($task && $task != 'default')
		{
			$class = 'JController' . ucfirst($task);

			if (class_exists($class))
			{
				return new $class;
			}
		}
		else
		{
			$class = ucfirst($base) . 'ControllerDefault';

			if (class_exists($class))
			{
				return new $class;
			}
		}

		// Nothing found. Panic.
		throw new RuntimeException(sprintf('Class %s not found', $class));
	}

	/**
	 * Gets a configuration value.
	 *
	 * @param   string  $varname  The name of the value to get.
	 * @param   string  $default  Default value to return
	 *
	 * @return  mixed  The user state.
	 *
	 * @since   1.0
	 */
	public function getCfg($varname, $default = null)
	{
		return JFactory::getConfig()->get($varname, $default);
	}

	/**
	 * Gets the client id of the current running application.
	 *
	 * @return  integer  A client identifier.
	 *
	 * @since   1.0
	 */
	public function getClientId()
	{
		return $this->clientId;
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
		return md5(JFactory::getConfig()->get('secret') . $seed);
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
			$session      = JFactory::getSession();
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
	 * Returns the application JMenu object.
	 *
	 * @param   string  $name     The name of the application/client.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JMenu  JMenu object.
	 *
	 * @since   1.0
	 */
	public function getMenu($name = null, $options = array())
	{
		if (!isset($name))
		{
			$name = $this->name;
		}

		try
		{
			$menu = JMenu::getInstance($name, $options);
		}
		catch (Exception $e)
		{
			return null;
		}

		return $menu;
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
	 * @param string $component
	 *
	 * @return  JRegistry  Component params
	 *
	 * @since   1.0
	 */
	public function getParams($component = '')
	{
		return $component ? JComponentHelper::getParams($component) : new JRegistry;
	}

	/**
	 * Returns the application JRouter object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JRouter  A JRouter object
	 *
	 * @since   1.0
	 */
	public static function getRouter($name = null, array $options = array())
	{
		if (!isset($name))
		{
			$name = JFactory::getApplication()->getName();
		}

		jimport('joomla.application.router');

		try
		{
			$router = JRouter::getInstance($name, $options);
		}
		catch (Exception $e)
		{
			return null;
		}

		return $router;
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
	abstract public function getTemplate($params = false);

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
		$registry = JFactory::getSession()->get('registry');

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
	 * Is admin interface?
	 *
	 * @return  boolean  True if this application is administrator.
	 *
	 * @since   1.0
	 */
	public function isAdmin()
	{
		return ($this->clientId == 1);
	}

	/**
	 * Is site interface?
	 *
	 * @return  boolean  True if this application is site.
	 *
	 * @since   1.0
	 */
	public function isSite()
	{
		return ($this->clientId == 0);
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
					$privateKey = JApplication::getHash(@$_SERVER['HTTP_USER_AGENT']);

					$key      = new JCryptKey('simple', $privateKey, $privateKey);
					$crypt    = new JCrypt(new JCryptCipherSimple, $key);
					$rcookie  = $crypt->encrypt(serialize($credentials));
					$lifetime = time() + 365 * 24 * 60 * 60;

					// Use domain and path set in config for cookie if it exists.
					$cookie_domain = $this->getCfg('cookie_domain', '');
					$cookie_path   = $this->getCfg('cookie_path', '/');
					setcookie(JApplication::getHash('JLOGIN_REMEMBER'), $rcookie, $lifetime, $cookie_path, $cookie_domain);
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
			$cookie_domain = $this->getCfg('cookie_domain', '');
			$cookie_path   = $this->getCfg('cookie_path', '/');
			setcookie(self::getHash('JLOGIN_REMEMBER'), false, time() - 86400, $cookie_path, $cookie_domain);

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
			JFactory::getSession()->set('application.queue', $this->messageQueue);
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
		$registry = JFactory::getSession()->get('registry');

		if (!is_null($registry))
		{
			return $registry->set($key, $value);
		}

		return null;
	}
}
