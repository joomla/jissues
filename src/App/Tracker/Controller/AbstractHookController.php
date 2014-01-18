<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller;

use App\Projects\TrackerProject;
use App\Projects\Table\LabelsTable;
use App\Tracker\Table\ActivitiesTable;

use Joomla\Application\AbstractApplication;
use Joomla\Database\DatabaseDriver;
use Joomla\Date\Date;
use Joomla\Event\Dispatcher;
use Joomla\Event\Event;
use Joomla\Github\Github;
use Joomla\Input\Input;

use JTracker\Controller\AbstractTrackerController;

use JTracker\Database\AbstractDatabaseTable;
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
	 * The dispatcher object
	 *
	 * @var    Dispatcher
	 * @since  1.0
	 */
	protected $dispatcher;

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
	 * Flag if the event listener is set for a hook
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $listenerSet = false;

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
	 * @var    integer
	 * @since  1.0
	 */
	protected $debug;

	/**
	 * Logger object.
	 *
	 * @var    \Monolog\Logger
	 * @since  1.0
	 */
	protected $logger;

	/**
	 * The type of hook being executed
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type = 'standard';

	/**
	 * Registers the event listener for the current hook and project
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function addEventListener()
	{
		/*
		 * Add the event listener if it exists.  Listeners are named in the format of <project><type>Listener in the Hooks\Listeners namespace.
		 * For example, the listener for a joomla-cms pull activity would be JoomlacmsPullListener
		 */
		$baseClass = ucfirst(str_replace('-', '', $this->project->gh_project)) . ucfirst($this->type) . 'Listener';
		$fullClass = __NAMESPACE__ . '\\Hooks\\Listeners\\' . $baseClass;

		if (class_exists($fullClass))
		{
			$this->dispatcher->addListener(new $fullClass);
			$this->listenerSet = true;
		}
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

			$this->$this->container->get('app')->close();
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

			$this->$this->container->get('app')->close();
		}
	}

	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method allows chiaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		$this->debug = $this->container->get('app')->get('debug.hooks');

		// Initialize the logger
		$this->logger = new Logger('JTracker');

		$this->logger->pushHandler(
			new StreamHandler(
				$this->container->get('app')->get('debug.log-path') . '/github_' . strtolower($this->type) . '.log'
			)
		);

		// Get the event dispatcher
		$this->dispatcher = $this->container->get('app')->getDispatcher();

		// Get a database object
		$this->db = $this->container->get('db');

		// Instantiate Github
		$this->github = $this->container->get('gitHub');

		// Check the request is coming from GitHub
		$validIps = $this->github->meta->getMeta();

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$myIP = $parts[0];
		}
		else
		{
			$myIP = $this->container->get('app')->input->server->getString('REMOTE_ADDR');
		}

		if (!$this->checkIp($myIP, $validIps->hooks) && '127.0.0.1' != $myIP)
		{
			// Log the unauthorized request
			$this->logger->error('Unauthorized request from ' . $myIP);
			$this->$this->container->get('app')->close();
		}

		// Get the payload data
		$data = $this->container->get('app')->input->post->get('payload', null, 'raw');

		if (!$data)
		{
			$this->logger->error('No data received.');
			$this->$this->container->get('app')->close();
		}

		$this->logger->info('Data received - ' . ($this->debug ? print_r($data, 1) : ''));

		// Decode it
		$this->hookData = json_decode($data);

		// Get the project data
		$this->getProjectData();

		// Set up the event listener
		$this->addEventListener();

		return $this;
	}

	/**
	 * Set the logger.
	 *
	 * @param   LoggerInterface  $logger  The logger.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;

		return $this;
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
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function addActivityEvent($event, $dateTime, $userName, $projectId, $itemNumber, $commentId = null, $text = '', $textRaw = '')
	{
		$data = array();

		$date = new Date($dateTime);
		$data['created_date'] = $date->format($this->db->getDateFormat());

		$data['event'] = $event;
		$data['user']  = $userName;

		$data['project_id']    = (int) $projectId;
		$data['issue_number']  = (int) $itemNumber;
		$data['gh_comment_id'] = (int) $commentId;

		$data['text']     = $text;
		$data['text_raw'] = $textRaw;

		try
		{
			$activity = new ActivitiesTable($this->db);
			$activity->save($data);
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

			$this->$this->container->get('app')->close();
		}

		return $this;
	}

	/**
	 * Parse a text with GitHub Markdown.
	 *
	 * @param   string  $text  The text to parse.
	 *
	 * @return  string
	 *
	 * @since   1.0
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

			return '';
		}
	}

	/**
	 * Process labels for adding into the issues table
	 *
	 * @param   integer  $issueId  Issue ID to process
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	protected function processLabels($issueId)
	{
		try
		{
			$githubLabels = $this->github->issues->get($this->project->gh_user, $this->project->gh_project, $issueId)->labels;
		}
		catch (\DomainException $exception)
		{
			$this->logger->error(
				sprintf(
					'Error parsing the labels for GitHub issue %s/%s #%d - %s',
					$this->project->gh_user,
					$this->project->gh_project,
					$issueId,
					$exception->getMessage()
				)
			);

			return '';
		}

		$appLabelIds = array();

		// Make sure the label is present in the database by pulling the ID, add it if it isn't
		$query = $this->db->getQuery(true);

		foreach ($githubLabels as $label)
		{
			$query->clear()
				->select($this->db->quoteName('label_id'))
				->from($this->db->quoteName('#__tracker_labels'))
				->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote($label->name));

			$this->db->setQuery($query);
			$id = $this->db->loadResult();

			// If null, add the label
			if ($id === null)
			{
				$table = new LabelsTable($this->db);

				$data = array();
				$data['project_id'] = $this->project->project_id;
				$data['name']       = $label->name;
				$data['color']      = $label->color;

				try
				{
					$table->save($data);

					$id = $table->label_id;
				}
				catch (\RuntimeException $exception)
				{
					$this->logger->error(
						sprintf(
							'Error adding label %s for project %s/%s to the database: %s',
							$label->name,
							$this->project->gh_user,
							$this->project->gh_project,
							$exception->getMessage()
						)
					);
				}
			}

			// Add the ID to the array
			$appLabelIds[] = $id;
		}

		// Return the array as a string
		if (count($appLabelIds) === 0)
		{
			return '';
		}
		else
		{
			return implode(',', $appLabelIds);
		}
	}

	/**
	 * Triggers an event if a listener is set
	 *
	 * @param   string                 $eventName  Name of the event to trigger
	 * @param   AbstractDatabaseTable  $table      Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function triggerEvent($eventName, AbstractDatabaseTable $table)
	{
		if ($this->listenerSet)
		{
			$event = new Event($eventName);

			// Add the event params
			$event->addArgument('hookData', $this->hookData)
				->addArgument('table', $table)
				->addArgument('github', $this->github)
				->addArgument('logger', $this->logger)
				->addArgument('project', $this->project);

			// Trigger the event
			$this->dispatcher->triggerEvent($event);
		}
	}
}
