<?php
/**
 * @package     JTracker
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Joomla! Issue Tracker Application class for web hook instances
 *
 * @package     JTracker
 * @subpackage  Application
 * @since       1.0
 */
abstract class JApplicationHooks extends JApplicationWeb
{
	/**
	 * The database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * The data payload
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $hookData;

	/**
	 * The type of hook being activated
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $hookType;

	/**
	 * JGithub instance
	 *
	 * @var    JGithub
	 * @since  1.0
	 */
	protected $github;

	/**
	 * The application message queue.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $messageQueue = array();

	/**
	 * The project information of the project whose data has been received
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Initialize the logger
		$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
		$options['text_file'] = 'github_' . $this->hookType . '.php';
		JLog::addLogger($options);

		// Run the parent constructor
		parent::__construct();

		// Register the application to JFactory
		JFactory::$application = $this;

		// Get a database object
		$this->db = JFactory::getDbo();

		// Instantiate JGithub
		$this->github = new JGithub;

		// Get the data directly from the $_POST superglobal.  I've yet to make this work with JInput.
		$data = $_POST['payload'];

		// Decode it
		$this->hookData = json_decode($data);
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
		return $this;
	}

	/**
	 * Retrieves the project data from the database
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function getProjectData()
	{
		// Initialize the database
		$query = $this->db->getQuery(true);

		// Get the ID for the project on our tracker
		$query = $this->db->getQuery(true);
		$query->select('*');
		$query->from($this->db->quoteName('#__tracker_projects'));
		$query->where($this->db->quoteName('gh_project') . ' = ' . $this->db->quote($this->hookData->repository->name));
		$this->db->setQuery($query);

		try
		{
			$this->project = $this->db->loadObject();
		}
		catch (RuntimeException $e)
		{
			JLog::add(sprintf('Error retrieving the project ID for GitHub repo %s in the database: %s', $this->hookData->repository->name, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Make sure we have a valid project ID
		if (!$this->project->project_id)
		{
			JLog::add(sprintf('A project does not exist for the %s GitHub repo in the database, cannot add data for it.', $this->hookData->repository->name), JLog::INFO);
			$this->close();
		}
	}
}
