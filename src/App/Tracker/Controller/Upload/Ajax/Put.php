<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$files = $application->input->files->get('files');

		if (!empty($files))
		{
			$file = new File($application);

			// Prepare response data
			$host     = $application->get('uri')->base->host;
			$destName = md5(time() . $file->getName()) . '.' . $file->getExtension();
			$destDir  = $application->getProject()->project_id;
			$fullPath = $host . '/' . $application->get('system.upload_dir') . '/' . $destDir . '/' . $destName;

			$isImage = (false !== strpos($file->getMimetype(), 'image'));

			$alt = $isImage
				? 'screen shot ' . date('Y-m-d') . ' at ' . date('H i s')
				: trim($file->getName()) . '.' . $file->getExtension();

			$data = [
				[
					'url'          => $fullPath,
					'thumbnailUrl' => $fullPath,
					'name'         => $file->getName(),
					'type'         => $file->getMimetype(),
					'size'         => $file->getSize(),
					'alt'          => $alt,
					'isImage'      => $isImage,
					'deleteUrl'    => '/upload/delete/?file=' . $destName,
					'deleteType'   => 'POST',
					'editorId'     => $application->input->get('editorId'),
				],
			];

			// Do not pass the thumbnail if not an image
			if (!$isImage)
			{
				unset($data[0]['thumbnailUrl']);
			}

			// Try to upload file
			try
			{
				$file->upload($destName);
			}
			catch (\Exception $e)
			{
				$application->getLogger()->error('Failed uploading file', ['file' => json_encode($file), 'exception' => $e]);

				$errors = [];

				foreach ($file->getErrors() as $error)
				{
					$errors[] = g11n3t($error);
				}

				$data = [
					[
						'error' => $errors,
					],
				];
			}

			$this->response->files = $data;
		}
	}
}
