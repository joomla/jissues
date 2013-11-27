<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Upload\Ajax;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Container;

/**
 * Delete image controller class.
 *
 * @since  1.0
 */
class Delete extends AbstractAjaxController
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
		$this->getApplication()->getUser()->authorize('create');

		$file = $this->getInput()->getCmd('file');

		if (!empty($file))
		{
			$path  = JPATH_THEMES . '/' . $this->getApplication()->get('system.upload_dir') . '/' . $file;

			try
			{
				unlink($path);
			}
			catch (\Exception $e)
			{
				// We need to set 500 otherwise status will be 200
				header('HTTP/1.1 500 Internal Server Error', true, 500);

				throw new \RuntimeException($e->getMessage());
			}
		}
	}
}
