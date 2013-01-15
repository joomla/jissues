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
 * CLI Script to pull comments from GitHub issues and add them to the database
 *
 * NOTE: Since this pulls each GitHub Issue's comments separately and inserts each record to the database separately,
 * this will be a time consuming script.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplicationComments extends JApplicationCli
{
	/**
	 * Comment data from GitHub
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $comments = array();

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * JGithub object
	 *
	 * @var    JGithub
	 * @since  1.0
	 */
	protected $github;

	/**
	 * Array containing the issues from the database and their GitHub ID
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $issues;

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
	 */
	protected function doExecute()
	{
		$this->getProject();

		$this->db = JFactory::getDbo();

		// Set up JGithub
		$options = new JRegistry;

		// Ask if the user wishes to authenticate to GitHub.  Advantage is increased rate limit to the API.
		$this->out('Do you wish to authenticate to GitHub? [y]es / [[n]]o :', false);

		$resp = trim($this->in());

		if ($resp == 'y' || $resp == 'yes')
		{
			// Set the options
			$options->set('api.username', $this->config->get('github_user', ''));
			$options->set('api.password', $this->config->get('github_password', ''));
		}

		// Instantiate JGithub
		$this->github = new JGithub($options);

		// Get the issues and their GitHub ID from the database
		$this->getIssues();

		// Get the comments from GitHub now
		$this->getComments();

		// Process the comments now
		$this->processComments();
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
	 * Method to get the comments on items from GitHub
	 *
	 * @return  array  Issue data
	 *
	 * @since   1.0
	 */
	protected function getComments()
	{
		try
		{
			foreach ($this->issues as $issue)
			{
				$id = $issue->gh_id;
				$this->out('Retrieving comments for issue #' . $id . ' from GitHub.', true);

				$this->comments[$id] = $this->github->issues->getComments($this->project->gh_user, $this->project->gh_project, $id);
			}
		}
		// Catch any DomainExceptions and close the script
		catch (DomainException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}

		// Retrieved items, report status
		$this->out('Finished retrieving comments for all issues.', true);
	}

	/**
	 * Method to get the GitHub issues from the database
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function getIssues()
	{
		$rangeFrom = 0;
		$rangeTo   = 0;

		// Limit issues to process
		$this->out('GH issues to process? [[a]]ll / [r]ange :', false);

		$resp = trim($this->in());

		if ($resp == 'r' || $resp == 'range')
		{
			// Get the first GitHub issue (from)
			$this->out('Enter the first GitHub issue ID to process (from) :', false);
			$rangeFrom = (int) trim($this->in());

			// Get the ending GitHub issue (to)
			$this->out('Enter the latest GitHub issue ID to process (to) :', false);
			$rangeTo = (int) trim($this->in());
		}

		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName(array('id', 'gh_id')));
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('gh_id') . ' IS NOT NULL');
		$query->where($this->db->quoteName('project_id') . '=' . (int) $this->project->project_id);

		// Issues range selected?
		if ($rangeTo != 0 && $rangeTo >= $rangeFrom)
		{
			$query->where($this->db->quoteName('gh_id') . ' >= ' . (int) $rangeFrom);
			$query->where($this->db->quoteName('gh_id') . ' <= ' . (int) $rangeTo);
		}

		$this->db->setQuery($query);

		try
		{
			$this->issues = $this->db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function processComments()
	{
		// Initialize our database object
		$query = $this->db->getQuery(true);

		// Start processing the comments now
		foreach ($this->issues as $issue)
		{
			// First, we need to check if the issue is already in the database, we're injecting the GitHub comment ID for that
			foreach ($this->comments[$issue->gh_id] as $comment)
			{
				$query->clear();
				$query->select('COUNT(*)');
				$query->from($this->db->quoteName('#__activity'));
				$query->where($this->db->quoteName('gh_comment_id') . ' = ' . (int) $comment->id);
				$this->db->setQuery($query);

				try
				{
					$result = (int) $this->db->loadResult();
				}
				catch (RuntimeException $e)
				{
					$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
					$this->close();
				}

				// If we have something already, then move on to the next item
				if ($result >= 1)
				{
					continue;
				}

				// Store the item in the database
				$columnsArray = array(
					$this->db->quoteName('gh_comment_id'),
					$this->db->quoteName('issue_id'),
					$this->db->quoteName('user'),
					$this->db->quoteName('event'),
					$this->db->quoteName('text'),
					$this->db->quoteName('created')
				);

				// Parse the body through GitHub's markdown parser
				$body = $this->github->markdown->render($comment->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project);

				$query->clear();
				$query->insert($this->db->quoteName('#__activity'));
				$query->columns($columnsArray);
				$query->values(
					(int) $comment->id . ', '
					. (int) $issue->id . ', '
					. $this->db->quote($comment->user->login) . ', '
					. $this->db->quote('comment') . ', '
					. $this->db->quote($body) . ', '
					. $this->db->quote(JFactory::getDate($comment->created_at)->toSql())
				);
				$this->db->setQuery($query);

				try
				{
					$this->db->execute();
				}
				catch (RuntimeException $e)
				{
					$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
					$this->close();
				}
			}
			$this->out('Added comments for issue #' . $issue->gh_id . ' from GitHub.', true);
		}
	}
}

try
{
	$app = JApplicationCli::getInstance('TrackerApplicationComments');

	JFactory::$application = $app;

	$app->execute();
}
catch (Exception $e)
{
	echo $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();
}
