<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller\Ajax;

use App\Activity\Model\SnapshotModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to handle AJAX requests for the activity snapshot data
 *
 * @property-read   SnapshotModel  $model  Model object
 *
 * @since  1.0
 */
class ActivitySnapshot extends AbstractAjaxController
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
		$this->model = new SnapshotModel($this->getContainer()->get('db'));
		$this->model->setProject($application->getProject());

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
		$statusLabels = [
			1 => g11n3t('New'),
			2 => g11n3t('Confirmed'),
			3 => g11n3t('Pending'),
			4 => g11n3t('Ready To Commit'),
			6 => g11n3t('Needs Review'),
			7 => g11n3t('Information Required')
		];

		$iterator = $this->model->getOpenIssues();

		$title = g11n3t('Total Open Issues By Status');

		$ticks  = [];
		$counts = [];

		foreach ($iterator as $item)
		{
			// Create the status' container if it hasn't already been
			if (!isset($counts[$statusLabels[$item->status]]))
			{
				$counts[$statusLabels[$item->status]] = [0];
			}

			$counts[$statusLabels[$item->status]][0]++;
		}

		$dataByStatus = array_values($counts);
		$ticks        = array_keys($counts);
		$labels       = [];

		foreach ($ticks as $type)
		{
			$object        = new \stdClass;
			$object->label = $type;
			$labels[]      = $object;
		}

		$data         = [];
		$internalData = [];

		for ($i = 0; $i < count($counts); $i++)
		{
			$internalData[] = 0;
		}

		foreach ($dataByStatus as $key => $dataForOneStatus)
		{
			$row       = $internalData;
			$row[$key] = $dataForOneStatus[0];
			$data[]    = $row;
		}

		// Setup the response data
		$this->response->data = [$data, $ticks, $labels, $title];
	}
}
