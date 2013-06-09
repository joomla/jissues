<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker;

/**
 * Class ValidationException.
 *
 * @since  1.0
 */
class ValidationException extends \Exception
{
	protected $errors = array();

	/**
	 * Constructor.
	 *
	 * @param   string  $errors  The errors encountered during validation.
	 *
	 * @since  1.0
	 */
	public function __construct($errors)
	{
		$this->errors = $errors;

		parent::__construct('Validation failure', 3);
	}

	/**
	 * Get validation errors.
	 *
	 * @since  1.0
	 * @return array|string
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}
