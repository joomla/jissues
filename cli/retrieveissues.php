#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Bootstrap the Joomla Platform.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Bootstrap the Tracker application libraries.
require_once JPATH_LIBRARIES . '/tracker.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);


/**
 * CLI Script to pull open issues from GitHub and inject them to the database if not already present
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplicationRetrieve extends JApplicationCli
{
	/**
	 * JGithub object
	 *
	 * @var    JGithub
	 * @since  1.0
	 */
	protected $github;

	/**
	 * @var stdClass
	 */
	protected $project = null;

	/**
	 * Class constructor.
	 *
	 * @param   mixed  $input       An optional argument to provide dependency injection for the application's
	 *                              input object.  If the argument is a JInputCli object that object will become
	 *                              the application's input object, otherwise a default input object is created.
	 * @param   mixed  $config      An optional argument to provide dependency injection for the application's
	 *                              config object.  If the argument is a JRegistry object that object will become
	 *                              the application's config object, otherwise a default config object is created.
	 * @param   mixed  $dispatcher  An optional argument to provide dependency injection for the application's
	 *                              event dispatcher.  If the argument is a JEventDispatcher object that object will become
	 *                              the application's event dispatcher, if it is null then the default event dispatcher
	 *                              will be created based on the application's loadDispatcher() method.
	 *
	 * @see     loadDispatcher()
	 * @since   1.0
	 */
	public function __construct(JInputCli $input = null, JRegistry $config = null, JEventDispatcher $dispatcher = null)
	{
		parent::__construct($input, $config, $dispatcher);

		// Set the app as CLI.
		$this->set('cli_app', true);

		// Register the application to JFactory
		JFactory::$application = $this;
	}

	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
		$this->getProject();

		// Set up JGithub
		$options = new JRegistry;

		// Ask if the user wishes to authenticate to GitHub.  Advantage is increased rate limit to the API.
		$this->out('Do you wish to authenticate to GitHub? [y]es / [n]o :', false);

		$resp = trim($this->in());

		if ($resp == 'y' || $resp == 'yes')
		{
			// Set the options
			$options->set('api.username', $this->config->get('github_user', ''));
			$options->set('api.password', $this->config->get('github_password', ''));
		}

		// Instantiate JGithub
		$this->github = new JGithub($options);

		// Pull in the data from GitHub
		$issues = $this->getData();

		// Process the issues now
		$this->processIssues($issues);
	}

	/**
	 * Get the project.
	 *
	 * @todo this might go to a base class.
	 *
	 * @throws RuntimeException
	 * @throws UnderflowException
	 *
	 * @return TrackerApplicationRetrieve
	 */
	protected function getProject()
	{
		// @todo get the data from - a model ?
		$projects = JHtmlProjects::projects();

		$id = $this->input->getInt('project', $this->input->getInt('p'));

		if (!$id)
		{
			foreach ($projects as $i => $project)
			{
				$this->out(($i + 1) . ') ' . $project->title);
			}

			$this->out('Select a project: ', false);

			$resp = (int) trim($this->in());

			if (!$resp)
			{
				throw new UnderflowException('Aborted');
			}

			if (false == array_key_exists($resp - 1, $projects))
			{
				throw new RuntimeException('Invalid project');
			}

			$this->project = $projects[$resp - 1];
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->id == $id)
				{
					$this->project = $project;

					break;
				}
			}

			if (is_null($this->project))
			{
				throw new RuntimeException('Invalid project');
			}
		}

		return $this;
	}

	/**
	 * Method to pull the list of issues from GitHub
	 *
	 * @return  array  Issue data
	 *
	 * @since   1.0
	 */
	protected function getData()
	{
		try
		{
			$issues = array();

			foreach (array('open', 'closed') as $state)
			{
				$this->out('Retrieving ' . $state . ' items from GitHub.', true);
				$page = 0;
				do
				{
					$page++;
					$issues_more = $this->github->issues->getListByRepository(
						$this->project->gh_user, // Owner
						$this->project->gh_project, // Repository
						null, // Milestone
						$state, // State [ open | closed ]
						null, // Assignee
						null, // Creator
						null, // Labels
						'created', // Sort
						'asc', // Direction
						null, // Since
						$page, // Page
						100 // Count
					);
					$count       = is_array($issues_more) ? count($issues_more) : 0;
					$this->out('Retrieved batch of ' . $count . ' items from GitHub.', true);
					if ($count)
					{
						$issues = array_merge($issues, $issues_more);
					}
				} while ($count);
			}

			usort($issues, function ($a, $b)
			{
				return $a->number - $b->number;
			});
		}

			// Catch any DomainExceptions and close the script
		catch (DomainException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}

		// Retrieved items, report status
		$this->out('Retrieved ' . count($issues) . ' items from GitHub, checking database now.', true);

		return $issues;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @param   array  $issues  Array containing the issues pulled from GitHub
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function processIssues($issues)
	{
		// Initialize our database object
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$added = 0;

		// Start processing the pulls now
		foreach ($issues as $issue)
		{
			// First, query to see if the issue is already in the database
			$query->clear();
			$query->select('COUNT(*)');
			$query->from($db->quoteName('#__issues'));
			$query->where($db->quoteName('gh_id') . ' = ' . (int) $issue->number);
			$query->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);
			$db->setQuery($query);

			try
			{
				$result = $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
				$this->close();
			}

			// If we have something already, then move on to the next item
			if ($result >= 1)
			{
				$this->out('GitHub issue #' . $issue->number . ' is already in the tracker.', true);
				continue;
			}

			// Store the item in the database
			$table              = JTable::getInstance('Issue');
			$table->gh_id       = $issue->number;
			$table->title       = $issue->title;
			$table->description = $this->github->markdown->render($issue->body, 'gfm', 'JTracker/jissues');
			$table->status      = ($issue->state == 'open') ? 1 : 10;
			$table->opened      = JFactory::getDate($issue->created_at)->toSql();
			$table->modified    = JFactory::getDate($issue->updated_at)->toSql();
			$table->project_id  = $this->project->project_id;

			// Add the diff URL if this is a pull request
			if ($issue->pull_request->diff_url)
			{
				$table->patch_url = $issue->pull_request->diff_url;
			}

			// Add the closed date if the status is closed
			if ($issue->closed_at)
			{
				$table->closed_date = JFactory::getDate($issue->closed_at)->toSql();
			}

			// If the title has a [# in it, assume it's a Joomlacode Tracker ID
			// TODO - Would be better suited as a regex probably
			if (strpos($issue->title, '[#') !== false)
			{
				$pos          = strpos($issue->title, '[#') + 2;
				$table->jc_id = substr($issue->title, $pos, 5);
			}

			if (!$table->store())
			{
				$this->out($table->getError(), true);
				$this->close();
			}

			// Get the ID for the new issue
			$query->clear();
			$query->select('id');
			$query->from($db->quoteName('#__issues'));
			$query->where($db->quoteName('gh_id') . ' = ' . (int) $issue->number);
			$db->setQuery($query);

			try
			{
				$issueID = $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
				$this->close();
			}

			// Add a open record to the activity table
			$columnsArray = array(
				$db->quoteName('issue_id'),
				$db->quoteName('user'),
				$db->quoteName('event'),
				$db->quoteName('created')
			);

			$query->clear();
			$query->insert($db->quoteName('#__activity'));
			$query->columns($columnsArray);
			$query->values(
				(int) $issueID . ', '
				. $db->quote($issue->user->login) . ', '
				. $db->quote('open') . ', '
				. $db->quote($table->opened)
			);
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
				$this->close();
			}

			// Add a close record to the activity table if the status is closed
			if ($issue->closed_at)
			{
				$query->clear('values');
				$query->values(
					(int) $issueID . ', '
					. $db->quote($issue->user->login) . ', '
					. $db->quote('close') . ', '
					. $db->quote($table->closed_date)
				);
				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (RuntimeException $e)
				{
					$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
					$this->close();
				}
			}

			// Store was successful, update status
			$this->out('Added GitHub issue #' . $issue->number . ' to the tracker.', true);
			$added++;
		}

		// Update the final result
		$this->out('Added ' . $added . ' items to the tracker.', true);
	}
}

try
{
	$app = JApplicationCli::getInstance('TrackerApplicationRetrieve');

	JFactory::$application = $app;

	$app->execute();
}
catch (Exception $e)
{
	echo $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();
}
