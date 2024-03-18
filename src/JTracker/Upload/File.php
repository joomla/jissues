<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Upload;

use JTracker\Application\Application;
use Upload\File as UploadFile;
use Upload\Storage\FileSystem;
use Upload\Validation\Mimetype;
use Upload\Validation\Size;

/**
 * File upload class for the Joomla Tracker application.
 *
 * @since  1.0
 */
class File extends UploadFile implements \JsonSerializable
{
    /**
     * Application object.
     *
     * @var    Application
     * @since  1.0
     */
    private $application;

    /**
     * Constructor
     *
     * @param   Application  $application  The Application
     * @param   string       $key          The file's key in $_FILES superglobal
     *
     * @since   1.0
     */
    public function __construct(Application $application, $key = 'files')
    {
        $this->application = $application;

        $storage = new FileSystem(
            JPATH_THEMES . '/' . $this->application->get('system.upload_dir') . '/' . $this->application->getProject()->project_id
        );

        if (\is_array($_FILES[$key])) {
            $_FILES[$key]['name']       = $_FILES[$key]['name'][0];
            $_FILES[$key]['error']      = $_FILES[$key]['error'][0];
            $_FILES[$key]['tmp_name']   = $_FILES[$key]['tmp_name'][0];
        }

        parent::__construct($key, $storage);

        $this->setValidations();
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return  array
     *
     * @since   1.0
     */
    public function jsonSerialize(): array
    {
        return [
            'name'   => $this->getNameWithExtension(),
            'mime'   => $this->getMimetype(),
            'errors' => $this->getErrors(),
        ];
    }

    /**
     * Method to set the file validations.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function setValidations()
    {
        $validations = [
            new Mimetype($this->application->get('validation.mime_types')),
            new Size($this->application->get('validation.file_size')),
        ];

        // Txt has mime inconsistency on different environments,
        // so do not set mime validation for it.
        if ($this->getExtension() == 'txt') {
            array_shift($validations);
        }

        $this->addValidations($validations);
    }
}
