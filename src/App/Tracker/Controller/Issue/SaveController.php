<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;

use App\Tracker\Model\IssueModel;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save an item via the tracker component.
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

		$this->container->get('app')->getUser()->authorize('edit');
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
		$src = $this->container->get('app')->input->get('item', array(), 'array');

		try
		{
			$model = new IssueModel($this->container->get('db'));
			$model->save($src);

			$this->container->get('app')->enqueueMessage('The changes have been saved.', 'success');

			$this->getApplication()->redirect(
				'/tracker/' . $this->getApplication()->input->get('project_alias')
			);
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');

			if (!empty($src['id']))
			{
				$application->redirect(
					$application->get('uri.base.path')
					. 'tracker/' . $application->input->get('project_alias') . '/' . $src['id'] . '/edit'
				);
			}
			else
			{
				$application->redirect(
					$application->get('uri.base.path')
					. 'tracker/' . $application->input->get('project_alias')
				);
			}
		}

		parent::execute();
	}
}
