<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Tracker\Controller\Hooks\Listeners\AbstractListener;
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
		$label      = 'No Code Attached Yet';
		$labels     = array();
		$labelIsSet = $this->checkLabel($hookData, $github, $logger, $project, $label);

		if ($labelIsSet == false)
		{
			// Add the label as it isn't already set
			$labels[] = $label;
			$this->addLabels($hookData, $github, $logger, $project, $labels);
		}
	}
}
