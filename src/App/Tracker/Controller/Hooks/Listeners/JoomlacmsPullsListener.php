<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

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
			// Check pull requests for a PR-<branch> label
			$this->checkPullLabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

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
				$github->issues->comments->create(
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					'Pull requests to the master branch of this repo are not accepted.  '
					. 'Please close this pull request and submit a new one against the staging branch.'
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
		$issueLabel = 'PR-' . $hookData->pull_request->base->ref;
		$labelSet   = false;

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

		// Check if the PR- label present
		if (count($labels) > 0)
		{
			foreach ($labels as $label)
			{
				if (!$labelSet && $label->name == $issueLabel)
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

					$labelSet = true;
				}
			}
		}

		// Add the label if we need to
		if (!$labelSet)
		{
			try
			{
				$github->issues->labels->add(
					$project->gh_user, $project->gh_project, $hookData->pull_request->number, array($issueLabel)
				);

				// Post the new label on the object
				$logger->info(
					sprintf(
						'Added %s label to %s/%s #%d',
						$issueLabel,
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
						'Error adding the %s label to GitHub pull request %s/%s #%d - %s',
						$issueLabel,
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number,
						$e->getMessage()
					)
				);
			}
		}
	}
}
