<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Event listener for the joomla-cms items events.
 *
 * @since  1.0
 */
class TestsTestsListener extends AbstractListener implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTestAfterSubmit' => 'onTestAfterSubmit',
        ];
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
    public function onTestAfterSubmit(Event $event)
    {
        // Pull the arguments array
        $arguments = $event->getArguments();

        $status = $this->getHumanTestStatus($arguments['project'], $arguments['data']->testsSuccess, $arguments['data']->testsFailure, $arguments['issueNumber']);

        $this->createStatus($arguments['github'], $arguments['project'], $arguments['issueNumber'], $status);
    }
}
