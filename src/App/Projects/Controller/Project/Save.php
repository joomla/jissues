<?php

/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Controller\Project;

use App\Projects\Model\ProjectsModel;
use App\Projects\Table\ProjectsTable;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Controller\CsrfProtectedControllerInterface;

/**
 * Controller class to save a project.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController implements CsrfProtectedControllerInterface
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

        $this->model->setUser($this ->getContainer()->get('app')->getUser());

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
        $app = $this->getContainer()->get('app');

        $app->getUser()->authorize('admin');

        $activeProject = $app->getProject();

        $table = (new ProjectsTable($this->getContainer()->get('db')))
            ->save($app->getInput()->get('project', [], 'array'));

        // If the active project has been saved, its alias might have changed
        if ((int) $activeProject->project_id === (int) $table->project_id) {
            $app->getSession()->set('project_alias', $table->alias);
        }

        // Reload the project.
        $app->getProject(true);

        return parent::execute();
    }
}
