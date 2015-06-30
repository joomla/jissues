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
 * Event listener for the joomla-cms issues hook
 *
 * @since  1.0
 */
class JoomlacmsIssuesListener extends AbstractListener
{
	/**
	 * Event for after issues are created in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onIssueAfterCreate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Only perform these events if this is a new issue, action will be 'opened'
		if ($arguments['action'] === 'opened')
		{
			// Add a "no code" label
			$this->checkNoCodelabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);
		}
	}

	/**
	 * Adds a "No Code Attached Yet" label
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
	protected function checkNoCodelabel($hookData, Github $github, Logger $logger, $project)
	{
		// Set some data
		$codeLabel    = 'No Code Attached Yet';
		$codeLabelSet = false;

		// Get the labels for the issue
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

		// Check if the label is present only if there are already labels attached to the item
		if (count($labels) > 0)
		{
			foreach ($labels as $label)
			{
				if (!$codeLabelSet && $label->name == $codeLabel)
				{
					$logger->info(
						sprintf(
							'GitHub item %s/%s #%d already has the %s label.',
							$project->gh_user,
							$project->gh_project,
							$hookData->issue->number,
							$codeLabel
						)
					);

					$codeLabelSet = true;
				}
			}
		}

		// Add the label if it isn't already set
		if (!$codeLabelSet)
		{
			$addLabels   = array();
			$addLabels[] = $codeLabel;
			$this->addLabels($hookData, Github $github, Logger $logger, $project, IssuesTable $table, $addLabels);
		}
	}
}
