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

		$this->checkIssueLabels($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

		// Only perform these events if this is a new issue, action will be 'opened'
		if ($arguments['action'] === 'opened')
		{
			// Add a "no code" label
			$this->checkNoCodelabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);
		}
	}

	/**
	 * Event for after issues are created in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onIssueAfterUpdate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		$this->checkIssueLabels($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

		/*
		 * Only perform these events if this is a new issue, action will be 'opened'
		 * Generally this isn't necessary, however if the initial create webhook fails and someone redelivers the webhook from GitHub,
		 * then this will allow the correct actions to be taken
		 */
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
		$label      = 'No Code Attached Yet';
		$labels     = [];
		$labelIsSet = $this->checkLabel($hookData, $github, $logger, $project, $label);

		if ($labelIsSet === false)
		{
			// Add the label as it isn't already set
			$labels[] = $label;
			$this->addLabels($hookData, $github, $logger, $project, $labels);
		}
	}

	/**
	 * Checks for issue label rules
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
	protected function checkIssueLabels($hookData, Github $github, Logger $logger, $project)
	{
		// Set some data
		$rfcLabel     = 'Request for Comment';
		$addLabels    = [];
		$removeLabels = [];

		$rfcIssue    = strpos($hookData->issue->title, '[RFC]') || substr($hookData->issue->title, 0, 5) === 'RFC';
		$rfcLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $rfcLabel);

		// Add the label if we have a RFC issue
		if ($rfcIssue && !$rfcLabelSet)
		{
			$addLabels[] = $rfcLabel;
		}
		// Remove the label if we don't have a RFC issue
		elseif (!$rfcIssue && $rfcLabelSet)
		{
			$removeLabels[] = $rfcLabel;
		}

		// Add the labels if we need
		if (!empty($addLabels))
		{
			$this->addLabels($hookData, $github, $logger, $project, $addLabels);
		}

		// Remove the labels if we need
		if (!empty($removeLabels))
		{
			$this->removeLabels($hookData, $github, $logger, $project, $removeLabels);
		}

		return;
	}
}
