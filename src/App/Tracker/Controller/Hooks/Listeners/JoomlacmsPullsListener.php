<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Tracker\Table\IssuesTable;
use App\Tracker\Controller\Hooks\Listeners\AbstractListener;
use Joomla\Event\Event;
use Joomla\Github\Github;

use Monolog\Logger;

/**
 * Event listener for the joomla-cms pull request hook
 *
 * @since  1.0
 */
class JoomlacmsPullsListener extends AbstractListener
{
	/**
	 * Event for after pull requests are created in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onPullAfterCreate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Only perform these events if this is a new pull, action will be 'opened'
		if ($arguments['action'] === 'opened')
		{
			// Check that pull requests have certain labels
			$this->checkPullLabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Check if the pull request targets the master branch
			$this->checkMasterBranch($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Check if the pull request targets the 2.5.x branch
			$this->check25Branch($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Place the JoomlaCode ID in the issue title if it isn't already there
			$this->updatePullTitle($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Set the status to pending
			$this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);
		}
	}

	/**
	 * Event for after pull requests are updated in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onPullAfterUpdate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Check that pull requests have certain labels
		$this->checkPullLabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

		// Place the JoomlaCode ID in the issue title if it isn't already there
		$this->updatePullTitle($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

		// Add a RTC label if the item is in that status
		$this->checkRTClabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
	}

	/**
	 * Checks for the RTC label
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkRTClabel($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// Set some data
		$RTClabel    = 'RTC';
		$rtcLabelSet = false;

		// Get the labels for the pull's issue
		try
		{
			$labels = $github->issues->get($project->gh_user, $project->gh_project, $hookData->pull_request->number)->labels;
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving labels for GitHub item %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					$e->getMessage()
				)
			);

			return;
		}

		// Check if the RTC label present if there are already labels attached to the item
		if (count($labels) > 0)
		{
			foreach ($labels as $label)
			{
				if (!$rtcLabelSet && $label->name == $RTClabel)
				{
					$logger->info(
						sprintf(
							'GitHub item %s/%s #%d already has the %s label.',
							$project->gh_user,
							$project->gh_project,
							$hookData->pull_request->number,
							$RTClabel
						)
					);

					$rtcLabelSet = true;
				}
			}
		}

		// Validation, if the status isn't RTC or the Label is set then go no further
		if ($rtcLabelSet == true && $table->status != 4)
		{
			// Remove the RTC label as it isn't longer set to RTC
			$removeLabels   = array();
			$removeLabels[] = 'RTC';
			$this->removeLabel($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $removeLabels);

			return;
		}

		// Add the RTC label as it isn't already set
		$addLabels   = array();
		$addLabels[] = 'RTC';
		$this->addLabels($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $addLabels);

		return;
	}

	/**
	 * Checks if a pull request targets the 2.5.x branch
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function check25Branch($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		if ($hookData->pull_request->base->ref == '2.5.x')
		{
			// Post a comment on the PR informing the user of end of support and close the item
			try
			{
				$appNote = sprintf(
					'<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
					'https://github.com/joomla/jissues', 'J!Tracker'
				);

				$github->issues->comments->create(
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					'Joomla! 2.5 is no longer supported.  Pull requests for this branch are no longer accepted.' . $appNote
				);

				$github->pulls->edit(
					$project->gh_user, $project->gh_project, $hookData->pull_request->number, null, null, 'closed'
				);

				// Update the local item now
				try
				{
					// TODO - We'll need to inject the DB object at some point
					$data = [
						'status'      => 10,
						'closed_date' => (new Date)->format('Y-m-d H:i:s'),
						'closed_by'   => 'jissues-bot'
					];

					$table->save($data);
				}
				catch (\Exception $e)
				{
					$logger->error(
						sprintf(
							'Error updating the state for issue %s/%s #%d on the tracker: %s',
							$project->gh_user,
							$project->gh_project,
							$hookData->pull_request->number,
							$e->getMessage()
						)
					);
				}

				// Log the activity
				$logger->info(
					sprintf(
						'Added unsupported branch comment to %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number
					)
				);
			}
			catch (\DomainException $e)
			{
				$logger->error(
					sprintf(
						'Error posting comment to GitHub pull request %s/%s #%d - %s',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number,
						$e->getMessage()
					)
				);
			}
		}
	}

	/**
	 * Checks if a pull request targets the master branch
	 *
	 * @param   object  $hookData  Hook data payload
	 * @param   Github  $github    Github object
	 * @param   Logger  $logger    Logger object
	 * @param   object  $project   Object containing project data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkMasterBranch($hookData, Github $github, Logger $logger, $project)
	{
		if ($hookData->pull_request->base->ref == 'master')
		{
			// Post a comment on the PR asking to open a pull against staging
			try
			{
				$appNote = sprintf(
					'<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
					'https://github.com/joomla/jissues', 'J!Tracker'
				);

				$github->issues->comments->create(
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					'Pull requests to the master branch of this repo are not accepted.  '
					. 'Please close this pull request and submit a new one against the staging branch.' . $appNote
				);

				// Log the activity
				$logger->info(
					sprintf(
						'Added incorrect branch comment to %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number
					)
				);
			}
			catch (\DomainException $e)
			{
				$logger->error(
					sprintf(
						'Error posting comment to GitHub pull request %s/%s #%d - %s',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number,
						$e->getMessage()
					)
				);
			}
		}
	}

	/**
	 * Checks for a PR-<branch> label
	 *
	 * @param   object  $hookData  Hook data payload
	 * @param   Github  $github    Github object
	 * @param   Logger  $logger    Logger object
	 * @param   object  $project   Object containing project data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkPullLabel($hookData, Github $github, Logger $logger, $project)
	{
		// Set some data
		$issueLabel       = 'PR-' . $hookData->pull_request->base->ref;
		$languageLabel    = 'Language Change';
		$addLabels        = array();
		$prLabelSet       = false;
		$languageLabelSet = false;

		// Get the labels for the pull's issue
		try
		{
			$labels = $github->issues->get($project->gh_user, $project->gh_project, $hookData->pull_request->number)->labels;
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving labels for GitHub item %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					$e->getMessage()
				)
			);

			return;
		}

		// Check for labels if there are already labels attached to the item
		if (count($labels) > 0)
		{
			foreach ($labels as $label)
			{
				if (!$prLabelSet && $label->name == $issueLabel)
				{
					$logger->info(
						sprintf(
							'GitHub item %s/%s #%d already has the %s label.',
							$project->gh_user,
							$project->gh_project,
							$hookData->pull_request->number,
							$issueLabel
						)
					);

					$prLabelSet = true;
				}

				if (!$languageLabelSet && $label->name == $languageLabel)
				{
					$logger->info(
						sprintf(
							'GitHub item %s/%s #%d already has the %s label.',
							$project->gh_user,
							$project->gh_project,
							$hookData->pull_request->number,
							$languageLabel
						)
					);

					$languageLabelSet = true;
				}
			}
		}

		// Add the issueLabel if it isn't already set
		if (!$prLabelSet)
		{
			$addLabels[] = $issueLabel;
		}

		// If the language label is not set, check if we need to add it
		if (!$languageLabelSet)
		{
			// Get the files modified by the pull request
			try
			{
				$files = $github->pulls->getFiles($project->gh_user, $project->gh_project, $hookData->pull_request->number);
			}
			catch (\DomainException $e)
			{
				$logger->error(
					sprintf(
						'Error retrieving modified files for GitHub item %s/%s #%d - %s',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number,
						$e->getMessage()
					)
				);

				$files = array();
			}

			if (!empty($files))
			{
				$addLanguageLabel = false;

				foreach ($files as $file)
				{
					// Check for file paths administrator/language, installation/language, and language at position 0
					if (!$addLanguageLabel)
					{
						if (strpos($file->filename, 'administrator/language') === 0
							|| strpos($file->filename, 'installation/language') === 0
							|| strpos($file->filename, 'language') === 0)
						{
							$addLanguageLabel = true;
						}
					}
				}

				// Add the language label if need be
				if ($addLanguageLabel)
				{
					$addLabels[] = $languageLabel;
				}
			}
		}

		$this->addLabels($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $addLabels);

		return;
	}

	/**
	 * Updates the local application status for an item
	 *
	 * @param   Logger       $logger   Logger object
	 * @param   object       $project  Object containing project data
	 * @param   IssuesTable  $table    Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setPending(Logger $logger, $project, IssuesTable $table)
	{
		// Reset the issue status to pending and try updating the database
		try
		{
			$table->save(['status' => 3]);
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error setting the status to pending in local application for GitHub pull request %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$table->issue_number,
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Updates a pull request title to include the JoomlaCode ID if it exists
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function updatePullTitle($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// If the title already has the ID in it, then no need to do anything here
		if (preg_match('/\[#([0-9]+)\]/', $hookData->pull_request->title, $matches))
		{
			return;
		}

		// If we don't have a foreign ID, we can't do anything here
		if (is_null($table->foreign_number))
		{
			return;
		}

		// Define the new title
		$title = '[#' . $table->foreign_number . '] ' . $table->title;

		try
		{
			$github->pulls->edit(
				$project->gh_user, $project->gh_project, $hookData->pull_request->number, $title
			);

			// Post the new label on the object
			$logger->info(
				sprintf(
					'Updated the title for GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number
				)
			);
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error updating the title for GitHub pull request %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					$e->getMessage()
				)
			);

			// Don't change the title locally
			return;
		}

		// Update the local title now
		try
		{
			$data = ['title' => $title];
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$logger->error(
				sprintf(
					'Error updating the title for issue %s/%s #%d on the tracker: %s',
					$project->gh_user,
					$project->gh_project,
					$hookData->issue->number,
					$e->getMessage()
				)
			);
		}
	}
}
