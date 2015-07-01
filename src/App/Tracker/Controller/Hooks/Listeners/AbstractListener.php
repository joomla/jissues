<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Tracker\Table\IssuesTable;
use Joomla\Event\Event;
use Joomla\Github\Github;

use Monolog\Logger;

/**
 * Abstract listener class for custom Listeners
 *
 * @since  1.0
 */
abstract class AbstractListener
{
	/**
	 * Remove Labels
	 *
	 * @param   object       $hookData    Hook data payload
	 * @param   Github       $github      Github object
	 * @param   Logger       $logger      Logger object
	 * @param   object       $project     Object containing project data
	 * @param   IssuesTable  $table       Table object
	 * @param   string       $checkLabel  The label to check
	 *
	 * @return  bool         True if the label already exists
	 *
	 * @since   1.0
	 */
	protected function checkLabel($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $checkLabel)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueID($hookData);

		// Get the labels for the pull's issue
		try
		{
			$labels = $github->issues->get($project->gh_user, $project->gh_project, $issueNumber)->labels);
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving labels for GitHub item %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$issueNumber,
					$e->getMessage()
				)
			);

			return false;
		}

		// Check if the label present that return true
		if (count($labels) > 0)
		{
			foreach ($labels as $label)
			{
				if ($label->name == $checkLabel)
				{
					return true;
				}
			}
		}

		// Else return false
		return false;
	}

	/**
	 * Remove Labels
	 *
	 * @param   object       $hookData      Hook data payload
	 * @param   Github       $github        Github object
	 * @param   Logger       $logger        Logger object
	 * @param   object       $project       Object containing project data
	 * @param   IssuesTable  $table         Table object
	 * @param   array        $removeLabels  The labels to remove
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function removeLabels($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $removeLabels)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueID($hookData);

		// Only try to remove labels if the array isn't empty
		if (!empty($removeLabels))
		{
			// The foreach is needed as we have no array support on the `removeFromIssue` method
			foreach ($removeLabels as $removeLabel)
			{
				try
				{
					$github->issues->labels->removeFromIssue(
						$project->gh_user, $project->gh_project, $issueNumber, $removeLabel
					);

					// Post the new label on the object
					$logger->info(
						sprintf(
							'Removed %s label to %s/%s #%d',
							$removeLabel,
							$project->gh_user,
							$project->gh_project,
							$issueNumber
						)
					);
				}
				catch (\DomainException $e)
				{
					$logger->error(
						sprintf(
							'Error removing the %s label from GitHub pull request %s/%s #%d - %s',
							$removeLabel,
							$project->gh_user,
							$project->gh_project,
							$issueNumber,
							$e->getMessage()
						)
					);
				}
			}
		}
	}

	/**
	 * Get the correct issue ID if it is a Pull or Issue
	 *
	 * @param   object  $hookData  Hook data payload
	 *
	 * @return  string  The Issue/Pull ID
	 *
	 * @since   1.0
	 */
	protected function getIssueId($hookData)
	{
		if (isset($hookData->pull_request->number)
		{
			return $hookData->pull_request->number;
		}

		if (isset($hookData->issue->number)
		{
			return $hookData->issue->number;
		}
	}

	/**
	 * Add Labels
	 *
	 * @param   object       $hookData   Hook data payload
	 * @param   Github       $github     Github object
	 * @param   Logger       $logger     Logger object
	 * @param   object       $project    Object containing project data
	 * @param   IssuesTable  $table      Table object
	 * @param   array        $addLabels  The labels to add
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function addLabels($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $addLabels)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueID($hookData);

		// Only try to add labels if the array isn't empty
		if (!empty($addLabels))
		{
			try
			{
				$github->issues->labels->add(
					$project->gh_user, $project->gh_project, $issueNumber, $addLabels
				);

				// Post the new label on the object
				$logger->info(
					sprintf(
						'Added %s labels to %s/%s #%d',
						count($addLabels),
						$project->gh_user,
						$project->gh_project,
						$issueNumber
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
						$issueNumber,
						$e->getMessage()
					)
				);
			}
		}
	}
}
