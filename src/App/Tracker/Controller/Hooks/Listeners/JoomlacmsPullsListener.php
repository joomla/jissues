<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Tracker\Table\IssuesTable;

use Joomla\Event\Event;
use Joomla\Github\Github;

use Monolog\Logger;

/**
 * Event listener for the joomla-cms pull request hook
 *
 * @since  1.0
 */
class JoomlacmsPullsListener
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
			$this->checkPullLabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Check if the pull request targets the master branch
			$this->checkMasterBranch($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);
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
	protected function checkPullLabel($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// Set some data
		$issueLabel = 'PR-' . $hookData->pull_request->base->ref;
		$addLabels  = array();
		$prLabelSet = false;

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

		// Check if the PR- label present if there are already labels attached to the item
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
			}
		}

		// Add the issueLabel if it isn't already set
		if (!$prLabelSet)
		{
			$addLabels[] = $issueLabel;
		}

		/*
		 * If we have a foreign ID in the IssuesTable object, then there is a JoomlaCode tracker
		 * NOTE: If someone ever changes these labels on GitHub, this has to be changed
		 */
		if (isset($table->foreign_number))
		{
			$addLabels[] = 'Has JoomlaCode Tracker Item';
		}
		else
		{
			$addLabels[] = 'Needs JoomlaCode Tracker Item';
		}

		try
		{
			$github->issues->labels->add(
				$project->gh_user, $project->gh_project, $hookData->pull_request->number, $addLabels
			);

			// Post the new label on the object
			$logger->info(
				sprintf(
					'Added %s labels to %s/%s #%d',
					count($addLabels),
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
					'Error adding labels to GitHub pull request %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					$e->getMessage()
				)
			);
		}
	}
}
