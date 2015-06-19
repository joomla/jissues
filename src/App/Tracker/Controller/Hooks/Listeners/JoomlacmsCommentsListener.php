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
 * Event listener for the joomla-cms Comments request hook
 *
 * @since  1.0
 */
class JoomlacmsCommentsListener
{
	/**
	 * Event for after Comments gets added to the Tracker
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onCommentAfterAddingComment(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Add a RTC label if the item is in that status
		$this->addRTClabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
	}

	/**
	 * Event for after Comments requests are updated in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onCommentAfterUpdate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Add a RTC label if the item is in that status
		$this->addRTClabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
	}

	/**
	 * Adds a RTC label
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
	protected function addRTClabel($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// Validation, if the status isn't RTC then go no further
		if ($table->status != 4)
		{
			return;
		}

		// Set some data
		$RTClabel    = 'RTC';
		$addLabels   = array();
		$rtcLabelSet = false;

		// Get the labels for the pull's issue
		try
		{
			$labels = $github->issues->get($project->gh_user, $project->gh_project, $hookData->issue->number)->labels;
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving labels for GitHub item %s/%s #%d - %s',
					$project->gh_user,
					$project->gh_project,
					$hookData->issue->number,
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
				if (!$rtcLabelSet && $label->name == $RTClabel)
				{
					$logger->info(
						sprintf(
							'GitHub item %s/%s #%d already has the %s label.',
							$project->gh_user,
							$project->gh_project,
							$hookData->issue->number,
							$RTClabel
						)
					);

					$rtcLabelSet = true;
				}
			}
		}

		// Add the RTC label if it isn't already set
		if (!$rtcLabelSet)
		{
			$addLabels[] = $RTClabel;
		}

		// Only try to add labels if the array isn't empty
		if (!empty($addLabels))
		{
			try
			{
				$github->issues->labels->add(
					$project->gh_user, $project->gh_project, $hookData->issue->number, $addLabels
				);

				// Post the new label on the object
				$logger->info(
					sprintf(
						'Added %s labels to %s/%s #%d',
						count($addLabels),
						$project->gh_user,
						$project->gh_project,
						$hookData->issue->number
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
						$hookData->issue->number,
						$e->getMessage()
					)
				);
			}
		}
	}
}
