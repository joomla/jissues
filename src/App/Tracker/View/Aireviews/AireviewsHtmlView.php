<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\View\Aireviews;

use App\Tracker\Model\AireviewsModel;
use App\Tracker\Table\AiReviewFindingsTable;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * The list view for the findings of imported AI reviews
 *
 * @since  1.0
 */
class AireviewsHtmlView extends AbstractTrackerHtmlView
{
    /**
     * Redefine the model so the correct type hinting is available.
     *
     * @var     AireviewsModel
     * @since   1.0
     */
    protected $model;

    /**
     * The active filters
     *
     * @var    array
     * @since  1.0
     */
    private $filters = [];

    /**
     * Set the active filters.
     *
     * @param   array  $filters  The filter values by name.
     *
     * @return  $this
     *
     * @since   1.0
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Method to render the view.
     *
     * @return  string  The rendered view.
     *
     * @throws  \RuntimeException
     * @since   1.0
     */
    public function render()
    {
        $this->addData('items', $this->model->getItems());
        $this->addData('pagination', $this->model->getPagination());
        $this->addData('total', $this->model->getTotal());
        $this->addData('project', $this->getProject());
        $this->addData('filters', $this->filters);
        $this->addData('kinds', AiReviewFindingsTable::KINDS);
        $this->addData('states', AiReviewFindingsTable::STATES);

        return parent::render();
    }
}
