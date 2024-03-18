<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

/**
 * Interface defining a tracker controller
 *
 * @since  1.0
 */
interface TrackerControllerInterface
{
    /**
     * Initialize the controller.
     *
     * @return  $this  Method allows chiaining
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function initialize();

    /**
     * Execute the controller.
     *
     * This is a generic method to execute and render a view and is not suitable for tasks.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function execute();

    /**
     * Returns the current app
     *
     * @return  string  The app being executed.
     *
     * @since   1.0
     */
    public function getApp();
}
