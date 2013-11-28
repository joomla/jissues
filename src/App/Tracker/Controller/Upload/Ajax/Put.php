<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Upload\Ajax;

use g11n\g11n;
use Upload\Validation\Mimetype;
use Upload\Validation\Size;

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
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		$this->getApplication()->getUser()->authorize('create');

		$files = $this->getInput()->files->get('files');

		if (!empty($files))
		{
			$file = new File('files', $this->getApplication());

			$file->addValidations(
				array(
					new Mimetype($this->getApplication()->get('validation.image.mime_types')),
					new Size($this->getApplication()->get('validation.image.file_size'))
				)
			);

			// Prepare response data
			$host       = $this->getApplication()->get('uri')->base->host;
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
