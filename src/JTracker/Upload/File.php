<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Upload;

use JTracker\Application;
use Upload\File as UploadFile;
use Upload\Storage\FileSystem;

/**
 * File upload class for the Joomla Tracker application.
 * It is just a wrapper for the \Upload\File class to allow read the first file in the files array.
 *
 * @since  1.0
 */
class File extends UploadFile
{
	/**
	 * Constructor
	 *
	 * @param   string       $key  The file's key in $_FILES superglobal
	 * @param   Application  $app  The Application.
	 */
	public function __construct($key, Application $app)
	{
		$storage = new FileSystem(JPATH_THEMES . '/' . $app->get('system.upload_dir'));

		if (is_array($_FILES[$key]))
		{
			$_FILES[$key]['name']       = $_FILES[$key]['name'][0];
			$_FILES[$key]['error']      = $_FILES[$key]['error'][0];
			$_FILES[$key]['tmp_name']   = $_FILES[$key]['tmp_name'][0];
		}

		parent::__construct($key, $storage);
	}
}
