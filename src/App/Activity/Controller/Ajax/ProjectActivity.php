<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller\Ajax;

use App\Activity\Model\ProjectActivityModel;

use JTracker\Application;
use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to handle AJAX requests for the user activity data
 *
 * @since  1.0
 */
class ProjectActivity extends AbstractAjaxController
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
		/** @var Application $application */
		$application = $this->getContainer()->get('app');

		// Setup the model to query our data
		$this->model = new ProjectActivityModel($this->getContainer()->get('db'));
		$this->model->setProject($application->getProject());

		$state = $this->model->getState();

		$state->set('list.limit', 25);
		$state->set('list.start', 0);
		$state->set('list.period', $application->input->getUint('period', 1));

		$this->model->setState($state);

		$items = $this->model->getIssueCounts();
		$state = $this->model->getState();

		$periodType   = $state->get('list.period');
		$activityType = $state->get('list.activity_type');

		$periodTitle = [1 => 'Weeks', 2 => 'Months', 3 => 'Quarters'];
		$axisLabels  = ['None', 'Week', '30 Days', '90 Days'];

		$periodText    = $periodTitle[$periodType];
		$axisLabelText = $axisLabels[$periodType];

		$title = sprintf(g11n3t('Issues Opened and Closed for Past Four %1$s'), $periodText);

		$ticks  = [];
		$counts = [];

		$counts['Opened'][] = (int) $items[0]->opened4;
		$counts['Opened'][] = (int) $items[0]->opened3;
		$counts['Opened'][] = (int) $items[0]->opened2;
		$counts['Opened'][] = (int) $items[0]->opened1;

		$counts['Fixed'][] = (int) $items[1]->fixed4;
		$counts['Fixed'][] = (int) $items[1]->fixed3;
		$counts['Fixed'][] = (int) $items[1]->fixed2;
		$counts['Fixed'][] = (int) $items[1]->fixed1;

		$counts['Other Closed'][] = (int) $items[1]->closed4;
		$counts['Other Closed'][] = (int) $items[1]->closed3;
		$counts['Other Closed'][] = (int) $items[1]->closed2;
		$counts['Other Closed'][] = (int) $items[1]->closed1;

		$endDate     = $items[0]->end_date;
		$periodDays  = [7, 7, 30, 90];
		$dayInterval = $periodDays[$periodType];

		$ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 4) - 1) . ' day')) . ' - ' . date('d M', strtotime($endDate . '-' . ($dayInterval * 3) . ' day'));
		$ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 3) - 1) . ' day')) . ' - ' . date('d M', strtotime($endDate . '-' . ($dayInterval * 2) . ' day'));
		$ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 2) - 1) . ' day')) . ' - ' . date('d M', strtotime($endDate . '-' . ($dayInterval * 1) . ' day'));
		$ticks[] = date('d M', strtotime($endDate . '-' . (($dayInterval * 1) - 1) . ' day')) . ' - ' . date('d M', strtotime($endDate . '-' . ($dayInterval * 0) . ' day'));

		$data          = [];
		$label1        = new \stdClass;
		$label2        = new \stdClass;
		$label3        = new \stdClass;
		$types         = array_keys($counts);
		$label1->label = $types[0];
		$label2->label = $types[1];
		$label3->label = $types[2];
		$data          = [$counts[$types[0]], $counts[$types[1]], $counts[$types[2]]];
		$labels        = [$label1, $label2, $label3];

		// Setup the response data
		$this->response->data = [$data, $ticks, $labels, $title];
	}
}
