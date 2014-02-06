<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Get\Project;

use App\Tracker\Table\ActivitiesTable;

use CliApp\Command\Get\Project;

use Joomla\Date\Date;

/**
 * Class for retrieving comments from GitHub for selected projects
 *
 * @since  1.0
 */
class Comments extends Project
{
	/**
	 * Comment data from GitHub
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items = array();

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Retrieve comments from GitHub.';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Retrieve Comments');

		$this->logOut('Start retrieve Comments')
			->selectProject()
			->setupGitHub()
			->fetchData()
			->processData()
			->out()
			->logOut('Finished');
	}

	/**
	 * Set the changed issues.
	 *
	 * @param   array  $changedIssueNumbers  List of changed issue numbers.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setChangedIssueNumbers(array $changedIssueNumbers)
	{
		$this->changedIssueNumbers = $changedIssueNumbers;

		return $this;
	}

	/**
	 * Method to get the comments on items from GitHub
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function fetchData()
	{
		if (!count($this->changedIssueNumbers))
		{
			return $this;
		}

		$this->out(sprintf('Fetching comments for <b>%d</b> modified issues from GitHub...', count($this->changedIssueNumbers)), false);

		$progressBar = $this->getProgressBar(count($this->changedIssueNumbers));

		$this->usePBar ? $this->out() : null;

		foreach ($this->changedIssueNumbers as $count => $issueNumber)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out(
				sprintf(
					'#%d (%d/%d):',
					$issueNumber, $count, count($this->changedIssueNumbers)
				),
				false
			);

			$page = 0;

			$this->items[$issueNumber] = array();

			do
			{
				$page++;

				$comments = $this->github->issues->comments->getList(
					$this->project->gh_user, $this->project->gh_project, $issueNumber, $page, 100
				);

				$this->checkGitHubRateLimit($this->github->issues->comments->getRateLimitRemaining());

				$count = is_array($comments) ? count($comments) : 0;

				if ($count)
				{
					$this->items[$issueNumber] = array_merge($this->items[$issueNumber], $comments);
				}

				$this->usePBar
					? null
					: $this->out($count . ' ', false);
			}

			while ($count);
		}

		$this->out()
			->outOK();

		return $this;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processData()
	{
		if (!$this->items)
		{
			$this->logOut('Everything is up to date.');

			return $this;
		}

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		// Initialize our query object
		$query = $db->getQuery(true);

		$this->out(sprintf('Processing comments for %d modified issue(s)...', count($this->items)));

		$adds = 0;
		$updates = 0;

		$count = 1;

		// Initialize our ActivitiesTable instance to insert the new record
		$table = new ActivitiesTable($db);

		// Start processing the comments now
		foreach ($this->items as $issueNumber => $comments)
		{
			if (!count($comments))
			{
				$this
					->out()
					->out('No comments for issue #' . $issueNumber);
			}
			else
			{
				$this
					->out()
					->out(
						sprintf(
							'Processing %1$d comments for issue # %2$d (%3$d/%4$d)',
							count($comments), $issueNumber, $count, count($this->items)
						)
					);

				$progressBar = $this->getProgressBar(count($comments));

				$this->usePBar ? $this->out() : null;

				foreach ($comments as $i => $comment)
				{
					$check = $db->setQuery(
						$query
							->clear()
							->select($table->getKeyName())
							->select($db->quoteName('updated_date'))
							->from($db->quoteName($table->getTableName()))
							->where($db->quoteName('gh_comment_id') . ' = ' . (int) $comment->id)
							->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
					)
						->loadObject();

					if ($check)
					{
						if (!$this->force)
						{
							// If we have something already, check if it needs an update...
							$d1 = new Date($check->updated_date);
							$d2 = new Date($comment->updated_at);

							if ($d1 == $d2)
							{
								// No update required
								$this->usePBar
									? $progressBar->update($i + 1)
									: $this->out('-', false);

								continue;
							}
						}

						$table->load($check->{$table->getKeyName()});

						$this->usePBar ? null : $this->out(($this->force ? 'F ' : '~ '), false);
					}
					else
					{
						// New item
						$table->reset();
						$table->{$table->getKeyName()} = null;

						$this->usePBar ? null : $this->out('+', false);
					}

					$table->gh_comment_id = $comment->id;
					$table->issue_number  = (int) $issueNumber;
					$table->project_id    = $this->project->project_id;
					$table->user          = $comment->user->login;
					$table->event         = 'comment';
					$table->text_raw      = $comment->body;

					$table->text = $this->github->markdown->render(
						$comment->body,
						'gfm',
						$this->project->gh_user . '/' . $this->project->gh_project
					);

					$this->checkGitHubRateLimit($this->github->markdown->getRateLimitRemaining());

					$table->created_date = (new Date($comment->created_at))->format('Y-m-d H:i:s');
					$table->updated_date = (new Date($comment->updated_at))->format('Y-m-d H:i:s');

					$table->store();

					if ($check)
					{
						++ $updates;
					}
					else
					{
						++ $adds;
					}

					$this->usePBar
						? $progressBar->update($i + 1)
						: null;
				}

				++ $count;
			}
		}

		$this->out()
			->outOK()
			->logOut(sprintf('%1$d added %2$d updated.', $adds, $updates));

		return $this;
	}
}
