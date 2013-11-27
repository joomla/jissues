<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Upload;

use Upload\File as UploadFile;
use Upload\Storage\Base as Storage;

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
	 * @param   string                $key      The file's key in $_FILES superglobal
	 * @param   \Upload\Storage\Base  $storage  The method with which to store file
	 */
	public function __construct($key, Storage $storage)
	{
		if (!is_array($_FILES[$key]))
		{
			parent::__construct($key, $storage);
		}
		else
		{
			$_FILES[$key]['name']       = $_FILES[$key]['name'][0];
			$_FILES[$key]['error']      = $_FILES[$key]['error'][0];
			$_FILES[$key]['tmp_name']   = $_FILES[$key]['tmp_name'][0];

			parent::__construct($key, $storage);
		}
	}
}
