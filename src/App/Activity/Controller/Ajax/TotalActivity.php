<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller\Ajax;

use App\Activity\Model\TotaluseractivityModel;

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

		$state = $this->model->getState();

		$enteredPeriod = $application->input->getUint('period', 1);

		$state->set('list.activity_type', $application->input->getUint('activity_type', 0));

		if ($enteredPeriod == 5)
		{
			$startDate = $application->input->getCmd('startdate');
			$endDate   = $application->input->getCmd('enddate');

			if ($this->datesValid($startDate, $endDate))
			{
				$state->set('list.startdate', $startDate);
				$state->set('list.enddate', $endDate);
			}
			else
			{
				$enteredPeriod = 1;
			}
		}

		$state->set('list.period', $enteredPeriod);

		$this->model->setState($state);
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
		$items = $this->model->getTotalActivity();
		$state = $this->model->getState();

		$periodType   = $state->get('list.period');
		$activityType = $state->get('list.activity_type');

		$periodTitle   = [1 => g11n3t('Weeks'), 2 => g11n3t('Months'), 3 => g11n3t('Quarters')];
		$axisLabels    = [g11n3t('None'), g11n3t('Week'), g11n3t('30 Days'), g11n3t('90 Days')];
		$periodText    = $periodTitle[$periodType];
		$axisLableText = $axisLabels[$periodType];

		$activityTypes = [g11n3t('All'), g11n3t('Tracker'), g11n3t('Test'), g11n3t('Code')];
		$activityText  = $activityTypes[$activityType];
		$title         = sprintf(g11n3t('%1$s Points for Past Four %2$s'), $activityText, $periodText);

		$ticks  = [];
		$points = [];

		// Build series arrays in reverse order for the chart
		foreach ($items as $item)
		{
			$group            = $item->activity_group;
			$points[$group][] = (int) $item->p4;
			$points[$group][] = (int) $item->p3;
			$points[$group][] = (int) $item->p2;
			$points[$group][] = (int) $item->p1;
		}

		$endDate     = $items[0]->end_date;
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

		$data          = [];
		$label1        = new \stdClass;
		$label2        = new \stdClass;

		/* $label3        = new \stdClass; */
		$types         = array_keys($points);
		$label1->label = sprintf(g11n3t('%1$s Points'), $types[0]);

		if ($activityType === 0)
		{
			$label2->label = sprintf(g11n3t('%1$s Points'), $types[1]);
			/* $label3->label = sprintf(g11n3t('%1$s Points'), $types[2]); */
			$data          = [$points[$types[0]], $points[$types[1]], /*$points[$types[2]]*/];
			$labels        = [$label1, $label2, /*$label3*/];
		}
		else
		{
			$data   = [$points[$types[0]]];
			$labels = [$label1];
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
