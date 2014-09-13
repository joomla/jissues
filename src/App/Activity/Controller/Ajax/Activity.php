<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller\Ajax;

use App\Activity\Model\ActivityModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to handle AJAX requests for the activity data
 *
 * @since  1.0
 */
class Activity extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		// Setup the model to query our data
		$this->model = new ActivityModel($this->getContainer()->get('db'), $this->getContainer()->get('app')->input);

		$state = $this->model->getState();

		$state->set('list.limit', 25);
		$state->set('list.start', 0);
		$state->set('list.period', $application->input->getInt('period', 1));
		$state->set('list.activity_type', $application->input->getInt('activity_type', 0));

		$enteredPeriod = $application->input->getInt('period', 1);

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

		$items = $this->model->getItems();
		$state = $this->model->getState();

		$periodType   = $state->get('list.period');
		$activityType = $state->get('list.activity_type');

		$periodTitle = [1 => '7 Days', 2 => '30 Days', 3 => '90 Days', 4 => '12 Months', 5 => 'Custom'];
		$periodText  = $periodTitle[$periodType];

		$activityTypes = ['All', 'Tracker', 'Test', 'Code'];
		$activityText  = $activityTypes[$activityType];

		if ($periodType == 5)
		{
			$start = date('d M Y', strtotime($state->get('list.startdate')));
			$end   = date('d M Y', strtotime($state->get('list.enddate')));
			$title = sprintf(g11n3t('%1$d Points From %2$d Through %3$d'), $activityText, $start, $end);
		}
		else
		{
			$title = sprintf(g11n3t('%1$d Points for Past %2$d'), $activityText, $periodText);
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

		$data          = [];
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
}
