<?php

/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Controller\Project;

use App\Projects\Model\ProjectModel;
use App\Projects\Model\ProjectsModel;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Controller\CsrfProtectedControllerInterface;

/**
 * Controller class to delete a project.
 *
 * @since  1.0
 */
class Delete extends AbstractTrackerController implements CsrfProtectedControllerInterface
{
    /**
     * The default view for the component
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultView = 'projects';

    /**
     * Model object
     *
     * @var    ProjectsModel
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

        $this->getContainer()->get('app')->getUser()->authorize('admin');

        $this->model->setUser($this->getContainer()->get('app')->getUser());

        return $this;
    }

    /**
     * Execute the controller.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function execute()
    {
        $app   = $this->getContainer()->get('app');
        $alias = $app->getInput()->get('project_alias');

        (new ProjectModel($this->getContainer()->get('db')))
            ->delete($alias);

        // The deleted project can no longer be the active project
        $app->getInput()->set('project_alias', null);

        if ($app->getSession()->get('project_alias') === $alias) {
            $app->getSession()->remove('project_alias');
        }

        // Reload the project
        $app->getProject(true);

        return parent::execute();
    }
}
