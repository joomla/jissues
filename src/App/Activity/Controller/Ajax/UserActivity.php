<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller\Ajax;

use App\Activity\Model\UseractivityModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to handle AJAX requests for the user activity data
 *
 * @property-read   UseractivityModel  $model  Model object
 *
 * @since  1.0
 */
class UserActivity extends AbstractAjaxController
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
		$this->model = new UseractivityModel($this->getContainer()->get('db'));
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
		$items = $this->model->getUserActivity();
		$state = $this->model->getState();

		$periodType   = $state->get('list.period');
		$activityType = $state->get('list.activity_type');

		$periodTitle = [1 => g11n3t('7 Days'), 2 => g11n3t('30 Days'), 3 => g11n3t('90 Days'), 4 => g11n3t('12 Months'), 5 => g11n3t('Custom')];
		$periodText  = $periodTitle[$periodType];

		$activityTypes = [g11n3t('All'), g11n3t('Tracker'), g11n3t('Test'), g11n3t('Code')];
		$activityText  = $activityTypes[$activityType];

		if ($periodType == 5)
		{
			$start = date('d M Y', strtotime($state->get('list.startdate')));
			$end   = date('d M Y', strtotime($state->get('list.enddate')));
			$title = sprintf(g11n3t('%1$s Points From %2$s Through %3$s'), $activityText, $start, $end);
		}
		else
		{
			$title = sprintf(g11n3t('%1$s Points for Past %2$s'), $activityText, $periodText);
		}

		$ticks         = [];
		$trackerPoints = [];
		$testPoints    = [];
		$codePoints    = [];

		// Build series arrays in reverse order for the chart
		$i = count($items);

		while ($i > 0)
		{
			$i--;
			$ticks[]         = $items[$i]->name;
			$trackerPoints[] = (int) $items[$i]->tracker_points;
			$testPoints[]    = (int) $items[$i]->test_points;
			$codePoints[]    = (int) $items[$i]->code_points;
		}

		$label1        = new \stdClass;
		$label2        = new \stdClass;
		$label3        = new \stdClass;
		$label1->label = g11n3t('Tracker Points');
		$label2->label = g11n3t('Test Points');
		$label3->label = g11n3t('Code Points');

		switch ($activityText)
		{
			case 'Tracker':
				$data   = [$trackerPoints];
				$labels = [$label1];
				break;

			case 'Test':
				$data   = [$testPoints];
				$labels = [$label2];
				break;

			case 'Code':
				$data   = [$codePoints];
				$labels = [$label3];
				break;

			case 'All':
			default:
				$data   = [$trackerPoints, $testPoints, $codePoints];
				$labels = [$label1, $label2, $label3];
				break;
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
