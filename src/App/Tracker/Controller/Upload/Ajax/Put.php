<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Upload\Ajax;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Upload\File;

/**
 * Upload images controller class.
 *
 * @since  1.0
 */
class Put extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		$files = $application->input->files->get('files');

		if (!empty($files))
		{
			$file = new File($application);

			// Prepare response data
			$host       = $application->get('uri')->base->host;
			$destName   = md5(time() . $file->getName()) . '.' . $file->getExtension();

			$data = array(
				array(
					'url' => $host . '/uploads/' . $destName,
					'thumbnailUrl' => $host . '/uploads/' . $destName,
					'name' => $file->getName(),
					'type' => $file->getMimetype(),
					'size' => $file->getSize(),
					'alt'  => 'screen shot ' . date('Y-m-d') . ' at ' . date('H i s'),
					'deleteUrl' => '/upload/delete/?file=' . $destName,
					'deleteType' => "POST",
				)
			);

			// Try to upload file
			try
			{
				$file->upload($destName);
			}
			catch (\Exception $e)
			{
				$errors = array();

				foreach ($file->getErrors() as $error)
				{
					$errors[] = g11n3t($error);
				}

				$data = array(
					array(
						'error' => $errors
					)
				);
			}

			$this->response->files = $data;
		}
	}
}
