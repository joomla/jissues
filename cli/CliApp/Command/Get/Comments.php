<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Tracker\Table\ActivitiesTable;

use CliApp\Command\TrackerCommandOption;

use Joomla\Date\Date;

use JTracker\Container;

/**
 * Class for retrieving comments from GitHub for selected projects
 *
 * @since  1.0
 */
class Comments extends Get
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

	/**
	 * @var    integer
	 * @since  1.0
	 */
	protected $rangeFrom = 0;

	/**
	 * @var    integer
	 * @since  1.0
	 */
	protected $rangeTo = 0;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

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

		$this->usePBar = $this->application->get('cli-application.progress-bar');

		if ($this->application->input->get('noprogress'))
		{
			$this->usePBar = false;
		}
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Retrieve Comments');

		$this->logOut('Start retrieve Comments')
			->selectProject()
			->selectRange()
			->setupGitHub()
			->displayGitHubRateLimit()
			->getIssues()
			->getComments()
			->processComments()
			->out()
			->logOut('Finished');
	}

	/**
	 * Select the range of issues to process.
	 *
	 * @return  Comments
	 *
	 * @since   1.0
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
			$this->out('<question>GitHub issues to process?</question> <b>[a]ll</b> / [r]ange :', false);

			$resp = trim($this->application->in());

			if ($resp == 'r' || $resp == 'range')
			{
				// Get the first GitHub issue (from)
				$this->out('<question>Enter the first GitHub issue ID to process (from):</question> ', false);
				$this->rangeFrom = (int) trim($this->application->in());

				// Get the ending GitHub issue (to)
				$this->out('<question>Enter the latest GitHub issue ID to process (to):</question> ', false);
				$this->rangeTo = (int) trim($this->application->in());
			}
		}

		return $this;
	}

	/**
	 * Method to get the GitHub issues from the database
	 *
	 * @return  Comments
	 *
	 * @since   1.0
	 */
	protected function getIssues()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = Container::getInstance()->get('db');

		$query = $db->getQuery(true);

		$query->select($db->quoteName('issue_number'))
			->from($db->quoteName('#__issues'))
			->where($db->quoteName('project_id') . '=' . (int) $this->project->project_id);

		// Issues range selected?
		if ($this->rangeTo != 0 && $this->rangeTo >= $this->rangeFrom)
		{
			$query->where($db->quoteName('issue_number') . ' >= ' . (int) $this->rangeFrom);
			$query->where($db->quoteName('issue_number') . ' <= ' . (int) $this->rangeTo);
		}

		$db->setQuery($query);

		$this->issues = $db->loadObjectList();

		return $this;
	}

	/**
	 * Method to get the comments on items from GitHub
	 *
	 * @return  Comments
	 *
	 * @since   1.0
	 */
	protected function getComments()
	{
		$this->out(sprintf('Retrieving <b>%d</b> issue comment(s) from GitHub...', count($this->issues)), false);

		$progressBar = $this->getProgressBar(count($this->issues));

		$this->usePBar ? $this->out() : null;

		foreach ($this->issues as $count => $issue)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out($count + 1 . '...', false);

			$page = 0;
			$this->comments[$issue->issue_number] = array();

			do
			{
				$page++;

				$comments = $this->github->issues->comments->getList(
					$this->project->gh_user, $this->project->gh_project, $issue->issue_number, $page, 100
				);

				$count = is_array($comments) ? count($comments) : 0;

				if ($count)
				{
					$this->comments[$issue->issue_number] = array_merge($this->comments[$issue->issue_number], $comments);

						$this->usePBar
						? null
						: $this->out($count . ' ', false);
				}
			}

			while ($count);
		}

		// Retrieved items, report status
		$this->out()
			->outOK();

		return $this;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  Comments
	 *
	 * @since   1.0
	 */
	protected function processComments()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = Container::getInstance()->get('db');

		// Initialize our database object
		$query = $db->getQuery(true);

		$this->out('Adding comments to the database...', false);

		$progressBar = $this->getProgressBar(count($this->issues));

		$this->usePBar ? $this->out() : null;

		$adds = 0;

		// Start processing the comments now
		foreach ($this->issues as $count => $issue)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out(($count + 1) . ':', false);

			// First, we need to check if the issue is already in the database,
			// we're injecting the GitHub comment ID for that
			foreach ($this->comments[$issue->issue_number] as $comment)
			{
				$query->clear()
					->select('COUNT(*)')
					->from($db->quoteName('#__activities'))
					->where($db->quoteName('gh_comment_id') . ' = ' . (int) $comment->id)
					->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);

				$db->setQuery($query);

				$result = (int) $db->loadResult();

				if ($result >= 1)
				{
					// If we have something already, then move on to the next item
					$this->usePBar ? null : $this->out('-', false);

					continue;
				}

				$this->usePBar ? null : $this->out('+', false);

				// Initialize our ActivitiesTable instance to insert the new record
				$table = new ActivitiesTable($db);

				$table->gh_comment_id = $comment->id;
				$table->issue_number  = (int) $issue->issue_number;
				$table->project_id    = $this->project->project_id;
				$table->user          = $comment->user->login;
				$table->event         = 'comment';
				$table->text_raw      = $comment->body;

				$table->text = $this->github->markdown->render(
					$comment->body,
					'gfm',
					$this->project->gh_user . '/' . $this->project->gh_project
				);

				$table->created_date = with(new Date($comment->created_at))->format('Y-m-d H:i:s');

				$table->store();

				++ $adds;
			}
		}

		$this->out()
			->outOK()
			->logOut(sprintf('Added %d new comments to the database', $adds));

		return $this;
	}
}
