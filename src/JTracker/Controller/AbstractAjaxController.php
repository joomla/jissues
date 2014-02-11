<?php
/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

/**
 * Abstract controller for AJAX requests
 *
 * @since  1.0
 */
abstract class AbstractAjaxController extends AbstractTrackerController
{
	/**
	 * AjaxResponse object.
	 *
	 * @var    AjaxResponse
	 * @since  1.0
	 */
	protected $response;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->response = new AjaxResponse;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  JSON response
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

		$this->getContainer()->get('app')->mimeType = 'application/json';

		return json_encode($this->response);
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
	 * Data object.
	 *
	 * @var    \stdClass
	 * @since  1.0
	 */
	public $data;

	/**
	 * Error message.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $error = '';

	/**
	 * Message string.
	 *
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
