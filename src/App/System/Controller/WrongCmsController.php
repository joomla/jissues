<?php

/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System\Controller;

use Joomla\Controller\AbstractController;
use Laminas\Diactoros\Response\TextResponse;

/**
 * Controller class to display a message to individuals looking for the wrong CMS
 *
 * @method  \JTracker\Application\Application getApplication()
 *
 * @since   1.0
 */
class WrongCmsController extends AbstractController
{
    /**
     * Execute the controller.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public function execute()
    {
        $this->getApplication()->setResponse(
            new TextResponse("This isn't the CMS you're looking for.", 404)
        );

        return true;
    }
}
