<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Hooks;

use Joomla\Application\AbstractApplication;
use Joomla\Date\Date;
use Joomla\Input\Input;
use Joomla\Log\Log;
use Joomla\Tracker\Components\Tracker\Controller\AbstractHookController;
use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;
use Joomla\Tracker\Components\Tracker\Table\IssuesTable;

/**
 * Controller class receive and inject issue comments from GitHub
 *
 * @since  1.0
 */
class ReceiveCommentsHook extends AbstractHookController
{
	/**
	 * Constructor.
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since  1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		// Set the type of hook
		$this->hookType = 'comments';

		// Run the parent constructor
		parent::__construct($input, $app);

		// Get the project data
		$this->getProjectData();
	}

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
			Log::add('Error checking the database for comment ID:' . $e->getMessage(), Log::INFO);
			$this->getApplication()->close();
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
			Log::add('Error checking the database for GitHub ID:' . $e->getMessage(), Log::INFO);
			$this->getApplication()->close();
		}

		// If we don't have an ID, we need to insert the issue
		if (!$issueID)
		{
			$this->insertIssue();
		}

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

		// Store was successful, update status
		Log::add(sprintf('Added GitHub comment %s to the tracker.', $this->hookData->comment->id), Log::INFO);

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
		$parsedText = $this->parseText($this->hookData->comment->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened     = new Date($this->hookData->issue->created_at);
		$modified   = new Date($this->hookData->issue->updated_at);

		$table = new IssuesTable($this->db);
		$table->issue_number    = $this->hookData->issue->number;
		$table->title           = $this->hookData->issue->title;
		$table->description     = $parsedText;
		$table->description_raw = $this->hookData->issue->body;
		$table->status		    = ($this->hookData->issue->state) == 'open' ? 1 : 10;
		$table->opened_date     = $opened->format($dateFormat);
		$table->modified_date   = $modified->format($dateFormat);
		$table->project_id      = $this->project->project_id;

		// Add the diff URL if this is a pull request
		if ($this->hookData->issue->pull_request->diff_url)
		{
			// $table->patch_url = $this->hookData->issue->pull_request->diff_url;
		}

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$table->closed_date = $closed->format($dateFormat);
			$table->closed_by = $this->hookData->issue->user->login;
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		// TODO - Would be better suited as a regex probably
		if (strpos($this->hookData->issue->title, '[#') !== false)
		{
			$pos = strpos($this->hookData->issue->title, '[#') + 2;
			$table->foreign_number = substr($this->hookData->issue->title, $pos, 5);
		}

		try
		{
			$table->store();
		}
		catch (\Exception $e)
		{
			Log::add(sprintf('Error storing new item %s in the database: %s', $this->hookData->issue->number, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Get the ID for the new issue
		$query = $this->db->getQuery(true);
		$query->select('id');
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->hookData->issue->number);
		$this->db->setQuery($query);

		$issueID = null;

		try
		{
			$issueID = $this->db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add(sprintf('Error retrieving ID for GitHub issue %s in the database: %s', $this->hookData->issue->number, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Add an open record to the activity table

		/* This should be done by the issues table object
		$activity = new ActivitiesTable($this->db);
		$activity->issue_number = (int) $this->hookData->issue->number;
		$activity->project_id   = $this->project->project_id;
		$activity->user         = $this->hookData->issue->user->login;
		$activity->event        = 'open';
		$activity->created_date = $table->opened_date;

		try
		{
			$activity->store();
		}
		catch (\Exception $e)
		{
			Log::add(sprintf('Error storing open activity for issue %s in the database: %s', $issueID, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}
		*/

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$table->closed_date,
				$this->hookData->issue->user->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Store was successful, update status
		Log::add(sprintf('Added GitHub issue %s to the tracker.', $this->hookData->issue->number), Log::INFO);

		return $issueID;
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
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__activities'));
		$query->set($this->db->quoteName('text') . ' = ' . $this->db->quote($parsedText));
		$query->set($this->db->quoteName('text_raw') . ' = ' . $this->db->quote($this->hookData->comment->body));
		$query->where($this->db->quoteName('id') . ' = ' . $id);

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add('Error updating the database for comment ' . $id . ':' . $e->getMessage(), Log::INFO);
			$this->getApplication()->close();
		}

		// Store was successful, update status
		Log::add(sprintf('Updated comment %s in the tracker.', $id), Log::INFO);

		return true;
	}
}
