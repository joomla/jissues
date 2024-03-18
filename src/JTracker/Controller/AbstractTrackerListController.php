<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

use JTracker\Application\Application;
use JTracker\Controller\Concerns\HasLists;

/**
 * Abstract Controller class for the Tracker Application
 *
 * @since  1.0
 */
abstract class AbstractTrackerListController extends AbstractTrackerController
{
    use HasLists;

    /**
     * Initialize the controller.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     */
    public function initialize()
    {
        parent::initialize();

        /** @var Application $application */
        $application = $this->getContainer()->get('app');

        $this->configurePaginationState($application, $this->model);

        return $this;
    }
}
