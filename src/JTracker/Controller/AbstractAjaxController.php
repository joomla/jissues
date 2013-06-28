<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;

/**
 * Class AbstractAjaxController
 *
 * @since  1.0
 */
abstract class AbstractAjaxController extends AbstractTrackerController
{
	/**
	 * @var AjaxResponse
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
	 * This is a generic method to execute and render a view and is not suitable for tasks.
	 *
	 * @return  void.
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
	 * @since  1.0
	 * @return void
	 */
	abstract protected function prepareResponse();
}

/**
 * Class AjaxResponse.
 *
 * @since  1.0
 */
class AjaxResponse
{
	/**
	 * @var \stdClass
	 */
	public $data;

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var string
	 */
	public $message = '';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->data = new \stdClass;
	}
}
