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
class JoomlacmsCommentsListener extends AbstractListener
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
	public function onCommentAfterCreate(Event $event)
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
							$hookData->issue->number,
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
}
