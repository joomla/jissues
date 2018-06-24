<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Controller\Ajax\Documentation;

use App\Support\Model\DefaultModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to view documentation pages.
 *
 * @since  1.0
 */
class Show extends AbstractAjaxController
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
		ob_start();

		/** @var $input \Joomla\Input\Input */
		$input = $this->getContainer()->get('app')->input;

		$page = $input->get('page');
		$path = $input->getPath('path');

		$base = $this->getContainer()->get('app')->get('uri')->base->path;

		$this->response->editLink = 'https://github.com/joomla/jissues/edit/master/Documentation/' . ($path ? $path . '/' : '') . $page . '.md';
		$this->response->permaLink = $base . 'documentation/view/?page=' . $page . ($path ? '&path=' . $path : '');

		$data = (new DefaultModel($this->getContainer()->get('db')))->getItem($page, $path)->text;

		$err = ob_get_clean();

		if ($err)
		{
			$this->response->error = $err;
		}
		else
		{
			$this->response->data = $data;
		}

		return;
	}
}
