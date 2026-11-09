<?php

/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Category;

use App\Tracker\Model\CategoryModel;
use App\Tracker\View\Category\CategoryHtmlView;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to edit an item of the category
 *
 * @since  1.0
 */
class Edit extends AbstractTrackerController
{
    /**
     * The default view for the component.
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultView = 'category';

    /**
     * The default layout for the component.
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultLayout = 'edit';

    /**
     * View object
     *
     * @var    CategoryHtmlView
     * @since  1.0
     */
    protected $view;

    /**
     * Model object
     *
     * @var    CategoryModel
     * @since  1.0
     */
    protected $model;

    /**
     * Execute the controller.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function execute()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');
        $project     = $application->getProject(true);
        $application->getUser()->authorize('manage');

        $this->view->setProject($project);
        $this->model->setProject($project);
        $item = $this->model->getItem($application->getInput()->getUint('id'));
        $this->view->setItem($item);

        return parent::execute();
    }
}
