<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomla! Issue Tracker Application class
 *
 * @package     BabDev.Tracker
 * @subpackage  Application
 * @since       1.0
 */
final class JApplicationTracker extends JApplicationWeb
{
	/**
	 * The scope of the application.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $scope = null;

	/**
	 * The application message queue.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $_messageQueue = array();

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

		// Register the event dispatcher
		$this->loadDispatcher();

		// Register the application to JFactory
		JFactory::$application = $this;
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

			// Fetch the controller
			$controller = $this->fetchController($component, $this->input->getCmd('task'));

			// Execute the component
			$contents = $this->executeComponent($controller, $component);
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
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		// For empty queue, if messages exists in the session, enqueue them first.
		if (!count($this->_messageQueue))
		{
			$session = JFactory::getSession();
			$sessionQueue = $session->get('application.queue');

			if (count($sessionQueue))
			{
				$this->_messageQueue = $sessionQueue;
				$session->set('application.queue', null);
			}
		}

		// Enqueue the message.
		$this->_messageQueue[] = array('message' => $msg, 'type' => strtolower($type));
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
		$output = ob_get_clean();

		return $output;
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

		// Nothing found. Panic.
		throw new RuntimeException('Class ' . $class . ' not found');
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
		$config = JFactory::getConfig();
		return $config->get($varname, $default);
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
		if (!count($this->_messageQueue))
		{
			$session = JFactory::getSession();
			$sessionQueue = $session->get('application.queue');

			if (count($sessionQueue))
			{
				$this->_messageQueue = $sessionQueue;
				$session->set('application.queue', null);
			}
		}

		return $this->_messageQueue;
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
	public static function getRouter($name = 'tracker', array $options = array())
	{
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
	public function getTemplate($params = false)
	{
		// Build the object
		$template = new stdClass;
		$template->template = 'protostar';
		$template->params   = new JRegistry;

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
		$session = JFactory::getSession();
		$registry = $session->get('registry');

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
		return false;
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
		return true;
	}

	/**
	 * Set the system message queue.
	 *
	 * @param   array  The information to set in the message queue
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setMessageQueue(array $queue = array())
	{
		$this->_messageQueue = $queue;
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
		$session = JFactory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry))
		{
			return $registry->set($key, $value);
		}

		return null;
	}
}
