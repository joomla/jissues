<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Hooks;

use Joomla\Date\Date;

use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Table\IssuesTable;
use JTracker\Authentication\GitHub\GitHubLoginHelper;

/**
 * Controller class receive and inject pull requests from GitHub.
 *
 *        >>>             !!!   N O T E   !!!                      <<<<<<<<<<<<<<<<<<<   !
 *                        ___________________
 *
 * This is basically the same code as the ReceiveIssuesHook.
 * But since it receives some more info we might use later,
 * I made it a separate class.
 *
 * @todo investigate unification   =;)
 *
 * @since  1.0
 */
class ReceivePullsHook extends AbstractHookController
{
	/**
	 * Data received from GitHub.
	 * @var  object
	 */
	protected $data;

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Pull or Issue ?
		$this->data = $this->hookData->pull_request;

		// $this->data = $this->hookData->issue;

		$issueID = 0;

		try
		{
			// Check to see if the issue is already in the database
			$issueID = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__issues'))
					->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
					->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->data->number)
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$this->logger->error('Error checking the database for the GitHub ID:' . $e->getMessage());
			$this->getApplication()->close();
		}

		// If the item is already in the database, update it; else, insert it.
		if ($issueID)
		{
			$this->updateData();
		}
		else
		{
			$this->insertData();
		}
	}

	/**
	 * Method to insert data for an issue from GitHub
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertData()
	{
		// Figure out the state based on the action
		$action = $this->hookData->action;

		switch ($action)
		{
			case 'closed':
				$status = 10;
				break;

			case 'opened' :
				// Issues: reopened
			case 'reopened' :
				// Pulls: synchronized
			case 'synchronized' :
			default:
				$status = 1;
				break;
		}

		$parsedText = $this->parseText($this->data->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened     = new Date($this->data->created_at);
		$modified   = new Date($this->data->updated_at);

		$data = array();
		$data['issue_number']    = $this->data->number;
		$data['title']           = $this->data->title;
		$data['description']     = $parsedText;
		$data['description_raw'] = $this->data->body;
		$data['status']          = $status;
		$data['opened_date']     = $opened->format($dateFormat);
		$data['opened_by']       = $this->data->user->login;
		$data['modified_date']   = $modified->format($dateFormat);
		$data['project_id']      = $this->project->project_id;
		$data['has_code']        = 1;

		// Add the closed date if the status is closed
		if ($this->data->closed_at)
		{
			$closed = new Date($this->data->closed_at);
			$data['closed_date'] = $closed->format($dateFormat);
			$data['closed_by']   = $this->hookData->sender->login;
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		if (preg_match('/\[#([0-9]+)\]/', $this->data->title, $matches))
		{
			$data['foreign_number'] = $matches[1];
		}

		try
		{
			$table = new IssuesTable($this->db);
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error adding GitHub pull request %s/%s #%d to the tracker: %s',
					$this->project->gh_user,
					$this->project->gh_project,
					$this->data->number,
					$e->getMessage()
				)
			);

			$this->getApplication()->close();
		}

		// Pull the user's avatar if it does not exist
		if (!file_exists(JPATH_THEMES . '/images/avatars/' . $this->data->user->login . '.png'))
		{
			GitHubLoginHelper::saveAvatar($this->data->user->login);
		}

		// Add a reopen record to the activity table if the action is reopened
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$data['modified_date'],
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->data->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$data['closed_date'],
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Added GitHub pull request %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->data->number
			)
		);

		// For joomla/joomla-cms, add PR-<branch> label
		if ($action == 'opened' && $this->project->gh_user == 'joomla' && $this->project->gh_project == 'joomla-cms')
		{
			// Extract some data
			$pullID     = $this->data->number;
			$target     = $this->data->base->ref;
			$issueLabel = 'PR-' . $target;
			$labelSet   = false;

			// Get the labels for the pull's issue
			$labels = $this->github->issues->get($this->project->gh_user, $this->project->gh_project, $pullID)->labels;

			$i = 0;

			// Check if the PR- label present
			do
			{
				if ($labels[$i]->name == $issueLabel)
				{
					$this->logger->info(
						sprintf(
							'Pull request %s/%s #%d already has branch label, skipping.',
							$this->project->gh_user,
							$this->project->gh_project,
							$this->data->number
						)
					);

					$labelSet = true;
				}

				$i++;
			}
			while (!$labelSet || $i < count($labels));

			// Add the label if we need to
			if (!$labelSet)
			{
				// Get the current labels so we don't lose them
				$currentLabels = array();

				foreach ($labels as $label);
				{
					$currentLabels[] = $label->name;
				}

				// Add the issue label
				$currentLabels[] = $issueLabel;

				// Post the new label on the object
				$this->logger->info(
					sprintf(
						'Adding branch label to %s/%s #%d',
						$this->project->gh_user,
						$this->project->gh_project,
						$this->data->number
					)
				);

				$this->github->issues->edit(
					$this->project->gh_user, $this->project->gh_project, $pullID, null, null, null, null, null, $currentLabels
				);
			}
		}

		return true;
	}

	/**
	 * Method to update data for an issue from GitHub
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function updateData()
	{
		// Figure out the state based on the action
		$action = $this->hookData->action;

		switch ($action)
		{
			case 'closed':
				$status = 10;
				break;

			case 'opened':
				// Issues: reopened
			case 'reopened' :
			default:
				$status = 1;
				break;
		}

		// Try to render the description with GitHub markdown
		$parsedText = $this->parseText($this->data->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$modified   = new Date($this->data->updated_at);

		$closed = null;

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$data = array();
		$data['title']           = $this->data->title;
		$data['description']     = $parsedText;
		$data['description_raw'] = $this->data->body;
		$data['status']          = $status;
		$data['modified_date']   = $modified->format($dateFormat);
		$data['modified_by']     = $this->hookData->sender->login;

		// Add the closed date if the status is closed
		if ($this->data->closed_at)
		{
			$closed = new Date($this->data->closed_at);
			$data['closed_date'] = $closed->format($dateFormat);
		}

		try
		{
			$table = new IssuesTable($this->db);
			$table->load(array('issue_number' => $this->data->number, 'project_id' => $this->project->project_id));
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error updating GitHub pull request %s/%s #%d to the tracker: %s',
					$this->project->gh_user,
					$this->project->gh_project,
					$this->data->number,
					$e->getMessage()
				)
			);

			$this->getApplication()->close();
		}

		// Add a reopen record to the activity table if the status is reopened
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$this->data->updated_at,
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a synchronize record to the activity table if the action is synchronized
		if ($action == 'synchronize')
		{
			$this->addActivityEvent(
				'synchronize',
				$this->data->updated_at,
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->data->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$this->data->closed_at,
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Updated GitHub pull request %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->data->number
			)
		);

		return true;
	}
}
