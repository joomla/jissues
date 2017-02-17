<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * AJAX Controller class to retrieve issue information
 *
 * @since  1.0
 */
class Info extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function prepareResponse()
	{
		$id = $this->getContainer()->get('app')->input->getUint('id');

		if (!$id)
		{
			throw new \RuntimeException('No id received.');
		}

		$item = (new IssueModel($this->getContainer()->get('db')))->getItem($id);

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
