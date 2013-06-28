<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller;

use App\Projects\TrackerProject;
use App\Tracker\Table\ActivitiesTable;

use Joomla\Application\AbstractApplication;
use Joomla\Database\DatabaseDriver;
use Joomla\Date\Date;
use Joomla\Factory;
use Joomla\Github\Github;
use Joomla\Input\Input;

use JTracker\Controller\AbstractTrackerController;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstract controller class for web hook requests
 *
 * @since  1.0
 */
abstract class AbstractHookController extends AbstractTrackerController implements LoggerAwareInterface
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
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Debug mode.
	 *
	 * @var integer
	 * @since  1.0
	 */
	protected $debug;

	/**
	 * Logger object.
	 *
	 * @var \Monolog\Logger
	 * @since  1.0
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @throws \RuntimeException
	 * @since  1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		// Run the parent constructor
		parent::__construct($input, $app);

		$this->debug = $this->getApplication()->get('debug.hooks');

		if (preg_match('/Receive([A-z]+)Hook/', get_class($this), $matches))
		{
			$fileName = $matches[1];
		}
		else
		{
			// Bad class name or regex :P
			$fileName = 'standard';
		}

		// Initialize the logger
		$this->logger = new Logger('JTracker');

		$this->logger->pushHandler(
			new StreamHandler(
				$this->getApplication()->get('debug.log-path') . '/github_' . strtolower($fileName) . '.log'
			)
		);

		// Get a database object
		$this->db = $this->getApplication()->getDatabase();

		// Instantiate Github
		$this->github = $this->getApplication()->getGitHub();

		// Check the request is coming from GitHub
		$validIps = $this->github->meta->getMeta();

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$myIP = $parts[0];
		}
		else
		{
			$myIP = $this->getInput()->server->getString('REMOTE_ADDR');
		}

		if (!$this->checkIp($myIP, $validIps->hooks) && '127.0.0.1' != $myIP)
		{
			// Log the unauthorized request
			$this->logger->error('Unauthorized request from ' . $myIP);
			$this->getApplication()->close();
		}

		// Get the payload data
		$data = $this->getInput()->post->get('payload', null, 'raw');

		if (!$data)
		{
			$this->logger->error('No data received.');
			$this->getApplication()->close();
		}

		$this->logger->info('Data received.' . ($this->debug ? print_r($data, 1) : ''));

		// Decode it
		$this->hookData = json_decode($data);

		// Get the project data
		$this->getProjectData();
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
		catch (\RuntimeException $e)
		{
			$this->logger->info(
				sprintf(
					'Error retrieving the project ID for GitHub repo %s in the database: %s',
					$this->hookData->repository->name,
					$e->getMessage()
				)
			);

			$this->getApplication()->close();
		}

		// Make sure we have a valid project ID
		if (!$this->project->project_id)
		{
			$this->logger->info(
				sprintf(
					'A project does not exist for the %s GitHub repo in the database, cannot add data for it.',
					$this->hookData->repository->name
				)
			);

			$this->getApplication()->close();
		}
	}

	/**
	 * Set a logger.
	 *
	 * @param   LoggerInterface  $logger  The logger.
	 *
	 * @since  1.0
	 * @return null|void
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Add a new event and store it to the database.
	 *
	 * @param   string   $event       The event name.
	 * @param   string   $dateTime    Date and time.
	 * @param   string   $userName    User name.
	 * @param   integer  $projectId   Project id.
	 * @param   integer  $itemNumber  THE item number.
	 * @param   integer  $commentId   The comment id
	 * @param   string   $text        The parsed html comment text.
	 * @param   string   $textRaw     The raw comment text.
	 *
	 * @since  1.0
	 * @return $this
	 */
	protected function addActivityEvent($event, $dateTime, $userName, $projectId, $itemNumber, $commentId = null, $text = '', $textRaw = '')
	{
		$activity = new ActivitiesTable($this->db);

		$date = new Date($dateTime);
		$activity->created_date = $date->format($this->db->getDateFormat());

		$activity->event = $event;
		$activity->user  = $userName;

		$activity->project_id    = (int) $projectId;
		$activity->issue_number  = (int) $itemNumber;
		$activity->gh_comment_id = (int) $commentId;

		$activity->text     = $text;
		$activity->text_raw = $textRaw;

		try
		{
			$activity->store();
		}
		catch (\Exception $exception)
		{
			$this->logger->info(
				sprintf(
					'Error storing %s activity to the database (ProjectId: %d, ItemNo: %d): %s',
					$event,
					$projectId, $itemNumber,
					$exception->getMessage()
				)
			);

			$this->getApplication()->close();
		}

		return $this;
	}

	/**
	 * Parse a text with GitHub Markdown.
	 *
	 * @param   string  $text  The text to parse.
	 *
	 * @since  1.0
	 * @return string
	 */
	protected function parseText($text)
	{
		try
		{
			return $this->github->markdown->render(
				$text,
				'gfm',
				$this->project->gh_user . '/' . $this->project->gh_project
			);
		}
		catch (\DomainException $exception)
		{
			$this->logger->info(
				sprintf(
					'Error parsing comment %d with GH Markdown: %s',
					$this->hookData->comment->id,
					$exception->getMessage()
				)
			);
		}

		$this->getApplication()->close();

		return '';
	}
}
