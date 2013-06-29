<?php
/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;

/**
 * Abstract controller for AJAX requests
 *
 * @since  1.0
 */
abstract class AbstractAjaxController extends AbstractTrackerController
{
	/**
	 * @var    AjaxResponse
	 * @since  1.0
	 */
	protected $response;

	/**
	 * Constructor.
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		$this->response = new AjaxResponse;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		ob_start();

		try
		{
			$this->prepareResponse();
		}
		catch (\Exception $e)
		{
			$this->response->error = $e->getMessage();
		}

		$errors = ob_get_clean();

		if ($errors)
		{
			$this->response->error .= $errors;
		}

		header('Content-type: application/json');

		echo json_encode($this->response);

		exit(0);
	}

	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	abstract protected function prepareResponse();
}

/**
 * AJAX response object
 *
 * @since  1.0
 */
class AjaxResponse
{
	/**
	 * @var    \stdClass
	 * @since  1.0
	 */
	public $data;

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $error = '';

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $message = '';

	/**
	 * Constructor
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		$this->data = new \stdClass;
	}
}
