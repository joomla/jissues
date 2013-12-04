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
use Upload\Validation\Mimetype;
use Upload\Validation\Size;

/**
 * File upload class for the Joomla Tracker application.
 * It is just a wrapper for the \Upload\File class to allow read the first file in the files array.
 *
 * @since  1.0
 */
class File extends UploadFile
{
	/**
	 * Application object.
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Constructor
	 *
	 * @param   Application  $app  The Application.
	 * @param   string       $key  The file's key in $_FILES superglobal
	 *
	 * @since   1.0
	 */
	public function __construct(Application $app, $key = 'files')
	{
		$this->app = $app;

		$storage = new FileSystem(JPATH_THEMES . '/' . $this->app->get('system.upload_dir'));

		if (is_array($_FILES[$key]))
		{
			$_FILES[$key]['name']       = $_FILES[$key]['name'][0];
			$_FILES[$key]['error']      = $_FILES[$key]['error'][0];
			$_FILES[$key]['tmp_name']   = $_FILES[$key]['tmp_name'][0];
		}

		parent::__construct($key, $storage);

		$this->setValidations();
	}

	/**
	 * Method to set file validations.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private function setValidations()
	{
		$this->addValidations(
			array(
				new Mimetype($this->app->get('validation.mime_types')),
				new Size($this->app->get('validation.file_size'))
			)
		);
	}
}
