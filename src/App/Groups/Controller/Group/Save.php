<?php

/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Controller\Group;

use App\Groups\Model\GroupModel;
use App\Groups\Table\GroupsTable;
use App\Groups\View\Group\GroupHtmlView;
use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save a group.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
    /**
     * The default view for the app.
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultView = 'groups';

    /**
     * Model object
     *
     * @var    GroupModel
     * @since  1.0
     */
    protected $model;

    /**
     * View object
     *
     * @var    GroupHtmlView
     * @since  1.0
     */
    protected $view;

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

        $this->getContainer()->get('app')->getUser()->authorize('manage');

        $this->model->setProject($this->getContainer()->get('app')->getProject());
        $this->view->setProject($this->getContainer()->get('app')->getProject());

        return $this;
    }

    /**
     * Execute the controller.
     *
     * @return  string  The rendered view.
     *
     * @since   1.0
     */
    public function execute()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $project = $application->getProject();
        $group   = $application->input->get('group', [], 'array');
        $table   = new GroupsTable($this->getContainer()->get('db'));

        $group['group_id'] = isset($group['group_id']) ? (int) $group['group_id'] : 0;

        if ($group['group_id']) {
            // Only groups of the current project may be modified.
            $table->load($group['group_id']);

            if ((int) $table->project_id !== (int) $project->project_id) {
                throw new AuthenticationException($application->getUser(), 'manage');
            }

            // The system flag can not be changed.
            $group['system'] = $table->system;
        } else {
            $group['system'] = 0;
        }

        $group['project_id'] = $project->project_id;

        $table->save($group);

        return parent::execute();
    }
}
