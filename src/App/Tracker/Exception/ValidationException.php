<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Exception;

/**
 * Class ValidationException.
 *
 * @since  1.0
 */
class ValidationException extends \Exception
{
    /**
     * Errors array
     *
     * @var    array|string
     * @since  1.0
     */
    protected $errors = [];

    /**
     * Constructor.
     *
     * @param   array|string  $errors  The errors encountered during validation.
     *
     * @since   1.0
     */
    public function __construct($errors)
    {
        $this->errors = $errors;

        parent::__construct('Validation failure', 3);
    }

    /**
     * Get validation errors.
     *
     * @return  array|string
     *
     * @since   1.0
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
