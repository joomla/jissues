<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Retrieve;

use CliApp\Command\TrackerCommandOption;
use Joomla\Date\Date;

use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;

use CliApp\Application\TrackerApplication;

/**
 * Class Comments.
 *
 * @since  1.0
 */
class Comments extends Retrieve
{
	/**
	 * Comment data from GitHub
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $comments = array();

	/**
	 * Array containing the issues from the database and their GitHub ID
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $issues;

	protected $rangeFrom = 0;

	protected $rangeTo = 0;

	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->description = 'Retrieve comments from GitHub.';

		$this->addOption(
			new TrackerCommandOption(
				'issue', '',
				'<n> Process only a single issue.'
			)
		)->addOption(
			new TrackerCommandOption(
				'all', '',
				'Process all issues.'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->application->outputTitle('Retrieve Comments');

		$this->selectProject()
			->selectRange()
			->setupGitHub()
			->displayGitHubRateLimit()
			// Get the issues and their GitHub ID from the database.
			->getIssues()
			// Get the comments from GitHub.
			->getComments()
			// Process the comments.
			->processComments();
	}

	/**
	 * Select the range of issues to process.
	 *
	 * @return $this
	 */
	protected function selectRange()
	{
		$issue = $this->application->input->getInt('issue');

		if ($issue)
		{
			$this->rangeFrom = $issue;
			$this->rangeTo   = $issue;
		}
		elseif ($this->application->input->get('all'))
		{
			// Do nothing
		}
		else
		{
			// Limit issues to process
			$this->out('GH issues to process? [[a]]ll / [r]ange :', false);

			$resp = trim($this->application->in());

			if ($resp == 'r' || $resp == 'range')
			{
				// Get the first GitHub issue (from)
				$this->out('Enter the first GitHub issue ID to process (from) :', false);
				$this->rangeFrom = (int) trim($this->application->in());

				// Get the ending GitHub issue (to)
				$this->out('Enter the latest GitHub issue ID to process (to) :', false);
				$this->rangeTo = (int) trim($this->application->in());
			}
		}

		return $this;
	}

	/**
	 * Method to get the GitHub issues from the database
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function getIssues()
	{
		$db = $this->application->getDatabase();

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'gh_id')))
			->from($db->quoteName('#__issues'))
			->where($db->quoteName('gh_id') . ' IS NOT NULL')
			->where($db->quoteName('project_id') . '=' . (int) $this->project->project_id);

		// Issues range selected?
		if ($this->rangeTo != 0 && $this->rangeTo >= $this->rangeFrom)
		{
			$query->where($db->quoteName('gh_id') . ' >= ' . (int) $this->rangeFrom);
			$query->where($db->quoteName('gh_id') . ' <= ' . (int) $this->rangeTo);
		}

		$db->setQuery($query);

		$this->issues = $db->loadObjectList();

		return $this;
	}

	/**
	 * Method to get the comments on items from GitHub
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function getComments()
	{
		$this->out(sprintf('Retrieving issue comments from GitHub (%d)...', count($this->issues)), false);

		foreach ($this->issues as $i => $issue)
		{
			$this->out($i + 1 . '...', false);

			$this->comments[$issue->gh_id] = $this->github->issues->comments->getList(
				$this->project->gh_user, $this->project->gh_project, $issue->gh_id
			);
		}

		// Retrieved items, report status
		$this->out('Finished.');

		return $this;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processComments()
	{
		$db = $this->application->getDatabase();

		// Initialize our database object
		$query = $db->getQuery(true);

		$this->out('Adding comments to the database...', false);

		// Start processing the comments now
		foreach ($this->issues as $i => $issue)
		{
			$this->out($i + 1 . '...', false);

			// First, we need to check if the issue is already in the database, we're injecting the GitHub comment ID for that
			foreach ($this->comments[$issue->gh_id] as $comment)
			{
				$query->clear()
					->select('COUNT(*)')
					->from($db->quoteName('#__activity'))
					->where($db->quoteName('gh_comment_id') . ' = ' . (int) $comment->id);

				$db->setQuery($query);

				$result = (int) $db->loadResult();

				if ($result >= 1)
				{
					// If we have something already, then move on to the next item
					continue;
				}

				// Initialize our JTableActivity instance to insert the new record
				$table = new ActivitiesTable($db);

				$table->gh_comment_id = $comment->id;
				$table->issue_id      = (int) $issue->id;
				$table->user          = $comment->user->login;
				$table->event         = 'comment';

				$table->text = $this->github->markdown->render(
					$comment->body,
					'gfm',
					$this->project->gh_user . '/' . $this->project->gh_project
				);

				$date           = new Date($comment->created_at);
				$table->created = $date->format('Y-m-d H:i:s');

				$table->store();
			}

		}

		$this->out('Finished');

		return $this;
	}
}
