<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class Info extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @since  1.0
	 * @throws \RuntimeException
	 * @return void
	 */
	protected function prepareResponse()
	{
		$id = $this->getApplication()->input->getUint('id');

		if (!$id)
		{
			throw new \RuntimeException('No id received.');
		}

		$model = new IssueModel;

		$item = $model->getItem($id);

		$issue = new \stdClass;

		// @todo add more info...
		$issue->comment_count = 0;
		$issue->opened_by     = $item->opened_by ? : 'n/a';

		foreach ($item->activities as $activity)
		{
			switch ($activity->event)
			{
				case 'comment':
					$issue->comment_count++;
					break;

				default :

					break;
			}
		}

		$this->response->data = $issue;
	}
}
