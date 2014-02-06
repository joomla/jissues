<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks;

use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Table\ActivitiesTable;
use App\Tracker\Table\IssuesTable;

use Joomla\Date\Date;

use JTracker\Authentication\GitHub\GitHubLoginHelper;

/**
 * Controller class receive and inject issue comments from GitHub
 *
 * @since  1.0
 */
class ReceiveCommentsHook extends AbstractHookController
{
	/**
	 * The type of hook being executed
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type = 'comments';

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$commentId = null;

		try
		{
			// Check to see if the comment is already in the database
			$commentId = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName('activities_id'))
					->from($this->db->quoteName('#__activities'))
					->where($this->db->quoteName('gh_comment_id') . ' = ' . (int) $this->hookData->comment->id)
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$this->logger->error('Error checking the database for comment ID:' . $e->getMessage());
			$this->container->get('app')->close();
		}

		// If the item is already in the database, update it; else, insert it
		if ($commentId)
		{
			$this->updateComment($commentId);
		}
		else
		{
			$this->insertComment();
		}
	}

	/**
	 * Method to insert data for acomment from GitHub
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertComment()
	{
		$issueID = null;

		try
		{
			// First, make sure the issue is already in the database
			$issueID = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__issues'))
					->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->hookData->issue->number)
					->where($this->db->quoteName('project_id') . ' = ' . $this->project->project_id)
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$this->logger->error('Error checking the database for GitHub ID:' . $e->getMessage());
			$this->container->get('app')->close();
		}

		// If we don't have an ID, we need to insert the issue and all comments, or we only insert the newly received comment
		if (!$issueID)
		{
			$this->insertIssue();

			$comments = $this->github->issues->comments->getList(
				$this->project->gh_user, $this->project->gh_project, $this->hookData->issue->number
			);

			foreach ($comments as $comment)
			{
				// Try to render the comment with GitHub markdown
				$parsedText = $this->parseText($comment->body);

				// Add the comment
				$this->addActivityEvent(
					'comment',
					$comment->created_at,
					$comment->user->login,
					$this->project->project_id,
					$this->hookData->issue->number,
					$comment->id,
					$parsedText,
					$comment->body
				);
			}
		}
		else
		{
			// Try to render the comment with GitHub markdown
			$parsedText = $this->parseText($this->hookData->comment->body);

			// Add the comment
			$this->addActivityEvent(
				'comment',
				$this->hookData->comment->created_at,
				$this->hookData->comment->user->login,
				$this->project->project_id,
				$this->hookData->issue->number,
				$this->hookData->comment->id,
				$parsedText,
				$this->hookData->comment->body
			);

			// Pull the user's avatar if it does not exist
			if (!file_exists(JPATH_THEMES . '/images/avatars/' . $this->hookData->comment->user->login . '.png'))
			{
				(new GitHubLoginHelper($this->container))
					->saveAvatar($this->hookData->comment->user->login);
			}
		}

		// Store was successful, update status
		$this->logger->info(
				sprintf(
				'Added GitHub comment %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->comment->id
			)
		);

		return true;
	}

	/**
	 * Method to insert data for an issue from GitHub
	 *
	 * @return  integer  Issue ID
	 *
	 * @since   1.0
	 */
	protected function insertIssue()
	{
		// Try to render the description with GitHub markdown
		$parsedText = $this->parseText($this->hookData->issue->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened     = new Date($this->hookData->issue->created_at);
		$modified   = new Date($this->hookData->issue->updated_at);

		$data = array();
		$data['issue_number']    = $this->hookData->issue->number;
		$data['title']           = $this->hookData->issue->title;
		$data['description']     = $parsedText;
		$data['description_raw'] = $this->hookData->issue->body;
		$data['status']          = ($this->hookData->issue->state) == 'open' ? 1 : 10;
		$data['opened_date']     = $opened->format($dateFormat);
		$data['opened_by']       = $this->hookData->issue->user->login;
		$data['modified_date']   = $modified->format($dateFormat);
		$data['project_id']      = $this->project->project_id;
		$data['build']           = $this->hookData->repository->default_branch;

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$data['closed_date'] = $closed->format($dateFormat);
			$data['closed_by']   = $this->hookData->sender->login;
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		if (preg_match('/\[#([0-9]+)\]/', $this->hookData->issue->title, $matches))
		{
			$data['foreign_number'] = $matches[1];
		}

		// Process labels for the item
		$data['labels'] = $this->processLabels($this->hookData->issue->number);

		try
		{
			$table = new IssuesTable($this->db);
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error adding GitHub issue %s/%s #%d to the tracker: %s',
					$this->project->gh_user,
					$this->project->gh_project,
					$this->hookData->issue->number,
					$e->getMessage()
				)
			);

			$this->container->get('app')->close();
		}

		$this->triggerEvent('onCommentAfterCreate', $table);

		// Pull the user's avatar if it does not exist
		if (!file_exists(JPATH_THEMES . '/images/avatars/' . $this->hookData->issue->user->login . '.png'))
		{
			(new GitHubLoginHelper($this->container))
				->saveAvatar($this->hookData->issue->user->login);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$data['closed_date'],
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Added GitHub issue %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->issue->number
			)
		);

		return $this;
	}

	/**
	 * Method to update data for an issue from GitHub
	 *
	 * @param   integer  $id  The comment ID
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function updateComment($id)
	{
		// Try to render the comment with GitHub markdown
		$parsedText = $this->parseText($this->hookData->comment->body);

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$data = array();
		$data['activities_id'] = $id;
		$data['text'] = $parsedText;
		$data['text_raw'] = $this->hookData->comment->body;

		try
		{
			$table = new ActivitiesTable($this->db);
			$table->load(array('activities_id' => $id));
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				'Error updating the database for comment ' . $id . ':' . $e->getMessage()
			);

			$this->container->get('app')->close();
		}

		$this->triggerEvent('onCommentAfterUpdate', $table);

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Updated comment %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$id
			)
		);

		return true;
	}
}
