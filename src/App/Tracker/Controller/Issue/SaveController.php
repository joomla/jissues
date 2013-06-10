<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use Joomla\Application\AbstractApplication;
use Joomla\Factory;
use Joomla\Input\Input;

use App\Tracker\Model\IssueModel;
use App\Tracker\ValidationException;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to add an item via the tracker component.
 *
 * @since  1.0
 */
class SaveController extends AbstractTrackerController
{
	/**
	 * Constructor
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		$this->getApplication()->getUser()->authorize('edit');
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		$src = $this->getInput()->get('item', array(), 'array');

		try
		{
			$model = new IssueModel;
			$model->save($src);

			$this->getApplication()->redirect(
				'/tracker/' . $this->getApplication()->input->get('project_alias')
			);
		}
		catch (ValidationException $e)
		{
			echo $e->getMessage();

			var_dump($e->getErrors());

			exit(255);

			// @todo move on to somewhere =;)

			// $this->getInput()->set('view', 'issue');
			// $this->getInput()->set('layout', 'edit');
		}

		parent::execute();
	}
}
