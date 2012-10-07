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
	 * Dispatch the application
	 *
	 * @param   string  $component  The component to dispatch.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function dispatch($component = null)
	{
		try
		{
			// Get the component if not set.
			if (!$component)
			{
				$component = $this->input->get('option', 'com_tracker');
			}

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

			// Render our view
			$contents = JComponentHelper::renderComponent($component);
			$document->setBuffer($contents, 'component');
		}

		// Mop up any uncaught exceptions.
		catch (Exception $e)
		{
			echo $e->getMessage();
			$this->close($e->getCode());
		}

		// Trigger the onAfterDispatch event
		$this->dispatcher->trigger('onAfterDispatch');
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
		// Dispatch the application
		$this->dispatch();
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
}
