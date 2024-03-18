<?php

/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\View\Projects;

use App\Projects\Model\ProjectsModel;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * The projects list view
 *
 * @since  1.0
 */
class ProjectsHtmlView extends AbstractTrackerHtmlView
{
    /**
     * The model object.
     *
     * @var    ProjectsModel
     * @since  1.0
     */
    protected $model;

    /**
     * Method to render the view.
     *
     * @return  string  The rendered view.
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function render()
    {
        $this->addData('projects', $this->model->getItems());

        return parent::render();
    }
}
