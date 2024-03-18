<?php

/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

use App\Users\Model\UserModel;
use App\Users\View\User\UserHtmlView;
use JTracker\Application\Application;
use JTracker\Controller\AbstractTrackerController;

/**
 * User controller class for the users component
 *
 * @since  1.0
 */
class User extends AbstractTrackerController
{
    /**
     * View object
     *
     * @var    UserHtmlView
     * @since  1.0
     */
    protected $view;

    /**
     * Model object
     *
     * @var    UserModel
     * @since  1.0
     */
    protected $model;

    /**
     * Initialize the controller.
     *
     * This will set up default model and view classes.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function initialize()
    {
        parent::initialize();

        /** @var Application $app */
        $app = $this->getContainer()->get('app');

        // If no ID is given, use the ID of the current user.
        $id = $app->getUser()->id;

        if (!$id) {
            throw new \UnexpectedValueException('Not authenticated.');
        }

        $this->view->id = (int) $id;

        $this->model->setProject($app->getProject());

        return $this;
    }
}
