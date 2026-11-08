<?php

/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller\Ajax;

use App\Activity\Model\TotaluseractivityModel;
use Joomla\Registry\Registry;
use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to handle AJAX requests for the user activity data
 *
 * @property-read   TotaluseractivityModel  $model  Model object
 *
 * @since  1.0
 */
class TotalActivity extends AbstractAjaxController
{
    /**
     * Initialize the controller.
     *
     * This will set up default model and view classes.
     *
     * @return  $this  Method allows chiaining
     *
     * @since   1.0
     */
    public function initialize()
    {
        $application = $this->getContainer()->get('app');

        // Setup the model to query our data
        $this->model = new TotaluseractivityModel($this->getContainer()->get('db'));
        $this->model->setProject($application->getProject());

        $state         = new Registry();
        $enteredPeriod = $application->input->getUint('period', 1);

        $activityType = $application->input->getUint('activity_type', 0);

        // Only the "All", "Tracker", "Test" and "Code" activity types are supported
        if ($activityType > 3) {
            $activityType = 0;
        }

        $state->set('list.activity_type', $activityType);

        if ($enteredPeriod == 5) {
            $startDate = $application->input->getCmd('startdate');
            $endDate   = $application->input->getCmd('enddate');

            if ($this->datesValid($startDate, $endDate)) {
                $state->set('list.startdate', $startDate);
                $state->set('list.enddate', $endDate);
            } else {
                $enteredPeriod = 1;
            }
        }

        // The chart only supports periods of weeks, months and quarters
        if (!\in_array($enteredPeriod, [1, 2, 3])) {
            $enteredPeriod = 1;
        }

        $state->set('list.period', $enteredPeriod);

        $this->model->setState($state);

        return $this;
    }

    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
        // Only users with view access to the project may see its statistics.
        $this->getContainer()->get('app')->getUser()->authorize('view');

        $items = $this->model->getTotalActivity();
        $state = $this->model->getState();

        $periodType   = $state->get('list.period');
        $activityType = $state->get('list.activity_type');

        $periodTitle = [
            1 => 'Weeks',
            2 => 'Months',
            3 => 'Quarters',
        ];
        $periodText  = $periodTitle[$periodType];

        // For the best translations, some languages need the ability to move the activity type segment, so translate each string individually
        $titles = [
            \sprintf('All Points for Past Four %s', $periodText),
            \sprintf('Tracker Points for Past Four %s', $periodText),
            \sprintf('Test Points for Past Four %s', $periodText),
            \sprintf('Code Points for Past Four %s', $periodText),
        ];

        $title = $titles[$activityType];

        $ticks  = [];
        $points = [];

        // Build series arrays in reverse order for the chart
        foreach ($items as $item) {
            $group            = $item->activity_group;
            $points[$group][] = (int) $item->p4;
            $points[$group][] = (int) $item->p3;
            $points[$group][] = (int) $item->p2;
            $points[$group][] = (int) $item->p1;
        }

        $endDate     = $items ? $items[0]->end_date : date('Y-m-d');
        $periodDays  = [7, 7, 30, 90];
        $dayInterval = $periodDays[$periodType];

        $ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 4) - 1) . ' day')) . ' - '
                    . date('d M', strtotime($endDate . '-' . ($dayInterval * 3) . ' day'));
        $ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 3) - 1) . ' day')) . ' - '
                    . date('d M', strtotime($endDate . '-' . ($dayInterval * 2) . ' day'));
        $ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 2) - 1) . ' day')) . ' - '
                    . date('d M', strtotime($endDate . '-' . ($dayInterval * 1) . ' day'));
        $ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 1) - 1) . ' day')) . ' - '
                    . date('d M', strtotime($endDate . '-' . ($dayInterval * 0) . ' day'));

        $data   = [];
        $labels = [];

        // The "All" type shows two series, the other types one; there might be less if no activity was found
        $seriesCount = $activityType === 0 ? 2 : 1;

        foreach (\array_slice($points, 0, $seriesCount, true) as $group => $groupPoints) {
            $label        = new \stdClass();
            $label->label = \sprintf('%1$s Points', $group);

            $data[]   = $groupPoints;
            $labels[] = $label;
        }

        // Setup the response data
        $this->response->data = [$data, $ticks, $labels, $title];
    }

    /**
     * Method to check that custom dates are valid
     *
     * @param   string  $date1  The first date.
     * @param   string  $date2  The second date.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    protected function datesValid($date1, $date2)
    {
        // Check that they are dates and that $date1 <= $date2
        return ($date1 == date('Y-m-d', strtotime($date1))) && ($date2 == date('Y-m-d', strtotime($date2))) && ($date1 <= $date2);
    }
}
