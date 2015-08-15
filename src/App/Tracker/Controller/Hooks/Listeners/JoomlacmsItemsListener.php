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
 * Event listener for the joomla-cms Item events.
 *
 * @since  1.0
 */
class JoomlacmsItemsListener extends AbstractListener
{
	/**
	 * Event for after item gets updated on the Tracker.
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onItemAfterSave(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Add a RTC label if the item is in that status
		$this->checkRTClabel($arguments['data'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
	}

	/**
	 * Event for after new item requests are submitted in the application.
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onItemAfterSubmit(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// New items can't have the RTC status.
		// Disabled $this->checkRTClabel($arguments['data'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
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
		$label      = 'RTC';
		$labels     = array();
		$labelIsSet = $this->checkLabel($hookData, $github, $logger, $project, $label);

		// Validation, if the status isn't RTC or the Label is set then go no further
		if ($labelIsSet == true && $table->status != 4)
		{
			// Remove the RTC label as it isn't longer set to RTC
			$labels[] = $label;
			$this->removeLabels($hookData, $github, $logger, $project, $labels);
		}

		if ($labelIsSet == false && $table->status == 4)
		{
			// Add the RTC label as it isn't already set
			$labels[] = $label;
			$this->addLabels($hookData, $github, $logger, $project, $labels);
		}
	}
}
