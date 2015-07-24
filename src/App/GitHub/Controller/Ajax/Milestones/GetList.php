<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Milestones;

/**
 * Controller class to display milestones on the GitHub repository.
 *
 * @since  1.0
 */
class GetList extends Base
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
		$this->getContainer()->get('app')->getUser()->authorize('manage');

		$this->response->data = $this->getList($this->getContainer()->get('app')->getProject());
	}
}
