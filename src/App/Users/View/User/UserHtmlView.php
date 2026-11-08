<?php

/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\View\User;

use App\Users\Model\UserModel;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * User view class for the Users component
 *
 * @since  1.0
 */
class UserHtmlView extends AbstractTrackerHtmlView
{
    /**
     * The model object.
     *
     * @var    UserModel
     * @since  1.0
     */
    protected $model;

    /**
     * Item ID
     *
     * @var    integer
     * @since  1.0
     */
    public $id = 0;

    /**
     * Method to render the view.
     *
     * @return  string  The rendered view.
     *
     * @since   1.0
     */
    public function render()
    {
        $item = $this->model->getItem($this->id);

        try {
            $timezone = new \DateTimeZone($item->params->get('timezone') ?: 'UTC');
        } catch (\Exception $e) {
            // Invalid timezone stored, fall back to UTC
            $timezone = new \DateTimeZone('UTC');
        }

        $this->addData('item', $item)
            ->addData('tz_offset', $timezone->getOffset(new \DateTime()) / 3600);

        return parent::render();
    }
}
