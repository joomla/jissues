<?php
/**
 * @package     JTracker\Application
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Application;

use Joomla\Application\AbstractWebApplication;
use Joomla\Database\DatabaseDriver;
use Joomla\Factory;
use Joomla\Github\Github;
use Joomla\Log\Log;

/**
 * Joomla! Issue Tracker Application class for web hook instances
 *
 * @package  JTracker\Application
 * @since    1.0
 */
abstract class AbstractHooksApplication extends AbstractWebApplication
{
	/**
	 * An array of how many addresses are in each CIDR mask
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $cidrRanges = array(
		16 => 65536,
		17 => 32768,
		18 => 16382,
		19 => 8192,
		20 => 4096,
		21 => 2048,
		22 => 1024,
		23 => 512,
		24 => 256,
		25 => 128,
		26 => 64,
		27 => 32,
		28 => 16,
		29 => 8,
		30 => 4,
		31 => 2,
		32 => 1
	);

	/**
	 * The database object
	 *
	 * @var    DatabaseDriver
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
	 * Github instance
	 *
	 * @var    Github
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
		Log::addLogger($options);

		// Run the parent constructor
		parent::__construct();

		// Register the application to JFactory
		Factory::$application = $this;

		// Get a database object
		$this->db = Factory::getDbo();

		// Instantiate Github
		$this->github = new Github;

		// Check the request is coming from GitHub
		$validIps = $this->github->meta->getMeta();

		if (!$this->checkIp($_SERVER['REMOTE_ADDR'], $validIps->hooks))
		{
			// Log the unauthorized request
			Log::add('Unauthorized request from ' . $_SERVER['REMOTE_ADDR'], Log::NOTICE);
			$this->close();
		}

		// Get the data directly from the $_POST superglobal.  I've yet to make this work with Input.
		$data = $_POST['payload'];

		// Decode it
		$this->hookData = json_decode($data);
	}

	/**
	 * Determines if the requestor IP address is in the authorized IP range
	 *
	 * @param   string  $requestor  The requestor's IP address
	 * @param   array   $validIps   The valid IP array
	 *
	 * @return  boolean  True if authorized
	 *
	 * @since   1.0
	 */
	protected function checkIp($requestor, $validIps)
	{
		foreach ($validIps as $githubIp)
		{
			// Split the CIDR address into a separate IP address and bits
			list ($subnet, $bits) = explode('/', $githubIp);

			// Convert the requestor IP and network address into number format
			$ip    = ip2long($requestor);
			$start = ip2long($subnet);
			$end   = $start + ($this->cidrRanges[(int) $bits] - 1);

			// Real easy from here, check to make sure the IP is in range
			if ($ip >= $start && $ip <= $end)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  AbstractHooksApplication
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
			Log::add(sprintf('Error retrieving the project ID for GitHub repo %s in the database: %s', $this->hookData->repository->name, $e->getMessage()), Log::INFO);
			$this->close();
		}

		// Make sure we have a valid project ID
		if (!$this->project->project_id)
		{
			Log::add(sprintf('A project does not exist for the %s GitHub repo in the database, cannot add data for it.', $this->hookData->repository->name), Log::INFO);
			$this->close();
		}
	}
}
