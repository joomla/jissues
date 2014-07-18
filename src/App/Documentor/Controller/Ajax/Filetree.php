<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Documentor\Controller\Ajax;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the developer documentation.
 *
 * @since  1.0
 */
class Filetree extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// A full file system path.
		$path = $this->getContainer()->get('app')->input->get('dir', '', 'HTML');

		$docuBase = JPATH_ROOT . '/Documentation';

		$path = $path ? : $docuBase;

		// Dumb spoof check
		$path = str_replace('..', '', $path);

		$response = [];

		$files = scandir($path);

		natcasesort($files);

		if (count($files) > 2)
		{
			$response[] = '<ul class="jqueryFileTree" style="display: none;">';

			// All dirs
			foreach ($files as $file)
			{
				if ($file != '.' && $file != '..' && is_dir($path . '/' . $file))
				{
					// Dumb spoof check
					$file = str_replace('..', '', $file);

					$response[] = '<li class="directory collapsed">'
						. '<a href="javascript:;" rel="' . $path . '/' . $file . '">' . htmlentities($file) . '</a>'
						. '</li>';
				}
			}

			// All files
			foreach ($files as $file)
			{
				if (!is_dir($path . '/' . $file) )
				{
					// Dumb spoof check
					$file = str_replace('..', '', $file);

					$subPath = trim(str_replace($docuBase, '', $path), '/');
					$page    = substr($file, 0, strrpos($file, '.'));
					$ext     = preg_replace('/^.*\./', '', $file);

					$fullPath = 'page=' . htmlentities($page) . ($subPath ? '&path=' . htmlentities($subPath) : '');

					$response[] = '<li class="file ext_' . $ext . '">'
						. '<a href="javascript:;" rel="' . $fullPath . '">'
						. $this->getTitle($file)
						. '</a>'
						. '</li>';
				}
			}

			$response[] = '</ul>';
		}

		return implode("\n", $response);
	}

	/**
	 * Generate a nice title.
	 *
	 * @param   string  $file  A file name.
	 *
	 * @since   1.0
	 * @return  string
	 */
	private function getTitle($file)
	{
		$title = substr($file, 0, strrpos($file, '.'));

		$title = str_replace(['-', '_'], ' ', $title);

		$title = ucfirst($title);

		$title = htmlentities($title);

		return $title;
	}
}
