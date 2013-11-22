<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Model\IssueModel;

use Joomla\Input\Input;
use JTracker\Controller\AbstractAjaxController;

/**
 * Add comments controller class.
 *
 * @since  1.0
 */
class Vote extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		/* @type Input $input */
		$input = $this->container->get('app')->input;

		$issue       = $input->getUint('issueId');
		$experienced = $input->getInt('experienced');
		$importance  = $input->getInt('importance');

		if (!$issue)
		{
			throw new \Exception('No issue ID received.');
		}

		if (!$importance)
		{
			throw new \Exception('Issue importance not received');
		}

		$model = new IssueModel($this->container->get('db'));

		$data = $model->vote($issue, $experienced, $importance);

		// Add the new score
		$data->importanceScore = $data->score / $data->votes;

		$this->response->data    = $data;
		$this->response->message = 'Vote successfully added';
	}
}
