<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
		// Verify the user has permissions to perform this action
		if (!$this->getContainer()->get('app')->getUser()->authorize('view'))
		{
			throw new \Exception('Anonymous votes are not allowed.');
		}

		/** @var Input $input */
		$input = $this->getContainer()->get('app')->input;

		$issue       = $input->getUint('issueId');
		$experienced = $input->getInt('experienced');
		$importance  = $input->getInt('importance');
		$userID      = $this->getContainer()->get('app')->getUser()->id;

		if (!$issue)
		{
			throw new \Exception('No issue ID received.');
		}

		if (!$importance)
		{
			throw new \Exception('Issue importance not received');
		}

		$data = (new IssueModel($this->getContainer()->get('db')))->vote($issue, $experienced, $importance, $userID);

		// Add the new score
		$data->importanceScore = $data->score / $data->votes;

		$this->response->data    = $data;
		$this->response->message = g11n3t('Vote successfully added');
	}
}
