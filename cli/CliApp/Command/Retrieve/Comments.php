<?php
/**
 * User: elkuku
 * Date: 25.04.13
 * Time: 14:58
 */

namespace CliApp\Command\Retrieve;

use Joomla\Date\Date;

use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;

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

	public function execute()
	{
		$this->selectProject()
			->setupGitHub()
			// Get the issues and their GitHub ID from the database.
			->getIssues()
			// Get the comments from GitHub.
			->getComments()
			// Process the comments.
			->processComments();
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
		$rangeFrom = 0;
		$rangeTo   = 0;

		$issue = $this->input->getInt('issue');

		if ($issue)
		{
			$rangeFrom = $issue;
			$rangeTo   = $issue;
		}
		elseif ($this->input->get('all'))
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
				$rangeFrom = (int) trim($this->application->in());

				// Get the ending GitHub issue (to)
				$this->out('Enter the latest GitHub issue ID to process (to) :', false);
				$rangeTo = (int) trim($this->application->in());
			}
		}

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'gh_id')));
		$query->from($db->quoteName('#__issues'));
		$query->where($db->quoteName('gh_id') . ' IS NOT NULL');
		$query->where($db->quoteName('project_id') . '=' . (int) $this->project->project_id);

		// Issues range selected?
		if ($rangeTo != 0 && $rangeTo >= $rangeFrom)
		{
			$query->where($db->quoteName('gh_id') . ' >= ' . (int) $rangeFrom);
			$query->where($db->quoteName('gh_id') . ' <= ' . (int) $rangeTo);
		}

		$db->setQuery($query);

		try
		{
			$this->issues = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->application->close();
		}

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
		try
		{
			foreach ($this->issues as $issue)
			{
				$id = $issue->gh_id;
				$this->out('Retrieving comments for issue #' . $id . ' from GitHub.', true);

				$this->comments[$id] = $this->github->issues->comments->getList($this->project->gh_user, $this->project->gh_project, $id);
			}
		}
		catch (\DomainException $e)
		{
			// Catch any DomainExceptions and close the script
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->application->close();
		}

		// Retrieved items, report status
		$this->out('Finished retrieving comments for all issues.', true);

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

		// Start processing the comments now
		foreach ($this->issues as $issue)
		{
			// First, we need to check if the issue is already in the database, we're injecting the GitHub comment ID for that
			foreach ($this->comments[$issue->gh_id] as $comment)
			{
				$query->clear();
				$query->select('COUNT(*)');
				$query->from($db->quoteName('#__activity'));
				$query->where($db->quoteName('gh_comment_id') . ' = ' . (int) $comment->id);
				$db->setQuery($query);

				$result = 0;

				try
				{
					$result = (int) $db->loadResult();
				}
				catch (\RuntimeException $e)
				{
					$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
					$this->application->close();
				}

				// If we have something already, then move on to the next item
				if ($result >= 1)
				{
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

			$this->out('Added comments for issue #' . $issue->gh_id . ' from GitHub.', true);
		}

		return $this;
	}
}
